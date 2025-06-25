#!/usr/bin/env Rscript

#' Merge Script
#'
#' This script merges two or more datasets into a new dataset.
#' It uses a configuration file to specify the datasets and their parameters.
#'
# ==============================================================================
suppressWarnings(suppressPackageStartupMessages({
  library(qiime2R)
  library(jsonlite)
  library(dplyr)
  library(tibble)
  library(readr)
  library(optparse)
}))
# =============================================================================
# CONSTANTS AND CONFIGURATION
# =============================================================================

PICRUST_TYPES <- c("picrust.ko", "picrust.pathways", "picrust.ec")
QIIME_VERSION <- "2025.5.28"
UUID_PATTERN <- "^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$"

# =============================================================================
# UTILITY FUNCTIONS
# =============================================================================

#' Load required packages with error handling
#' @param packages Character vector of package names
load_packages <- function(packages) {
  for (pkg in packages) {
    suppressWarnings(suppressPackageStartupMessages(
      library(pkg, character.only = TRUE)
    ))
  }
}

#' Validate file existence
#' @param filepath Path to file
#' @param name Descriptive name for error message
validate_file <- function(filepath, name = "File") {
  if (is.null(filepath) || !file.exists(filepath)) {
    stop(paste(name, "does not exist:", filepath))
  }
}

#' Safe file reading with trimmed whitespace
#' @param filepath Path to metadata file
#' @return Data frame with trimmed sample IDs
read_metadata_safe <- function(filepath) {
  validate_file(filepath, "Metadata file")
  metadata <- read.table(
    filepath,
    sep = "\t", header = TRUE, stringsAsFactors = FALSE
  )
  colnames(metadata)[1] <- "sample_id"
  metadata$sample_id <- trimws(metadata$sample_id)
  metadata
}

extract_samples_from_config <- function (config_data) {
  samples <- lapply(config_data$datasets, function (dataset) {
    unname(unlist(dataset$samples))
  })
  names(samples) <- names(config_data$datasets)
  samples
}

#' Read a TSV file safely with optional header check
#' @param filepath Path to the TSV file
#' @param name Descriptive name for the file (used in error messages)
#' @param check_header Logical indicating whether to check the header
read_tsv_safe <- function(filepath, name = "TSV file", check_header = TRUE) {
  validate_file(filepath, name)
  data <- read.table(
    filepath, sep = "\t", header = TRUE, stringsAsFactors = FALSE,
    check.names = check_header
  )
  data[] <- lapply(data, function(x) if (is.character(x)) trimws(x) else x)
  data
}

#' Write a data frame to a TSV file safely
#' @param data Data frame to write
#' @param filepath Path to the output TSV file
#' @param use_readr Logical indicating whether to use readr's write_tsv
write_tsv_safe <- function(data, filepath, use_readr = FALSE) {
  dir.create(dirname(filepath), recursive = TRUE, showWarnings = FALSE)
  rownames(data) <- NULL  # Ensure row names are not written
  if (use_readr) {
    write_tsv(
      x = data, file = filepath, col_names = TRUE, na = "NA", 
      quote = "needed", append = FALSE
    )
  } else {
    write.table(
      data, file = filepath, sep = "\t", row.names = FALSE,
      quote = FALSE, col.names = TRUE
    )
  }
}

#' Prepare metadata selection and renaming based on configuration
#' @param config_data Configuration data containing metadata pairing
prepare_metadata_by_dataset <- function (config_data) {
  pairing <- config_data$metadataPairing
  datasets <- names(config_data$datasets)
  select_by_dataset <- setNames(lapply(datasets, function(d)(c("sample_id"))), datasets)
  add_by_dataset    <- setNames(lapply(datasets, function(d)(character(0))), datasets)
  rename_by_dataset <- setNames(lapply(datasets, function(d)(character(0))), datasets)
  for (n in names(pairing)) {
    for (d in names(pairing[[n]]$datasets)) {
      select_by_dataset[[d]] <- c(select_by_dataset[[d]], pairing[[n]]$datasets[[d]])
      if (n != pairing[[n]]$datasets[[d]]) {
        rename_by_dataset[[d]] <- c(rename_by_dataset[[d]], setNames(n, pairing[[n]]$datasets[[d]]))
      }
    }
    for (d in names(pairing[[n]]$default_values)) {
      value <- pairing[[n]]$default_values[[d]]
      if (is.null(value)) value <- NA
      add_by_dataset[[d]] <- c(add_by_dataset[[d]], setNames(value, n))
    }
  }
  final_selection <- c("sample_id", names(pairing))
  list(
    select_by_dataset = select_by_dataset,
    add_by_dataset = add_by_dataset,
    rename_by_dataset = rename_by_dataset,
    final_selection = final_selection
  )
}

#' Generate a valid QIIME2 UUID
#' @return character A valid UUID in 8-4-4-4-12 format
generate_qiime_uuid <- function() {
  uuid_parts <- c(8, 4, 4, 4, 12)
  uuid_segments <- vapply(uuid_parts, function(n) {
    paste0(sample(c(letters[1:6], 0:9), n, replace = TRUE), collapse = "")
  }, character(1))
  
  return(paste(uuid_segments, collapse = "-"))
}

#' Create directory structure for QIIME2 artifact
#'
#' @param temp_dir character path to temporary directory
#' @param uuid character UUID for the artifact
#' @return character path to the artifact directory
create_artifact_structure <- function(temp_dir, uuid) {
  artifact_dir <- file.path(temp_dir, uuid)
  
  if (!dir.create(artifact_dir, recursive = TRUE, showWarnings = FALSE)) {
    stop("Failed to create artifact directory")
  }
  
  if (!dir.create(file.path(artifact_dir, "data"), recursive = TRUE, showWarnings = FALSE)) {
    stop("Failed to create data directory")
  }
  
  return(artifact_dir)
}

# =============================================================================
# MERGE FUNCTIONS
# =============================================================================

#' Merge Taxonomy tables from multiple datasets
#' @param config_data Configuration data containing dataset information
merge_taxonomy_data <- function(config_data) {
  taxonomy_files <- sapply(config_data$datasets, function (x)(x$files$taxonomy))
  if (is.null(taxonomy_files) || length(taxonomy_files) == 0) {
    stop("No taxonomy files specified in the configuration.")
  }
  taxonomy_data <- lapply(names(taxonomy_files), function(id) {
    read_tsv_safe(taxonomy_files[[id]], paste("Taxonomy file for dataset", id))
  })
  taxonomy_data <- unique(do.call(rbind, taxonomy_data))
  if (nrow(taxonomy_data) == 0) {
    stop("No taxonomy data found in the specified files.")
  }
  return(taxonomy_data)
}

#' Merge ASV tables from multiple datasets and updates list of samples and taxonomy
#' @param config_data Configuration data containing dataset information
#' @param samples_by_dataset List of samples for each dataset
#' @param taxonomy Taxonomy data frame
merge_asv_tables <- function(config_data, samples_by_dataset, taxonomy) {
  asv_files <- sapply(config_data$datasets, function (x)(x$files$asvTable))
  if (is.null(asv_files) || length(asv_files) == 0) {
    stop("No ASV files specified in the configuration.")
  }
  asv_data <- lapply(names(asv_files), function(id) {
    tmp <- read_tsv_safe(asv_files[[id]], paste("ASV file for dataset", id), FALSE)
    colnames(tmp)[1] <- "OTU.ID"
    samples <- intersect(samples_by_dataset[[id]], colnames(tmp))
    if (length(samples) == 0) {
      stop(paste("No samples found in dataset", id, "for ASV table."))
    }
    tmp <- tmp[, c("OTU.ID", samples)]
    samples_by_dataset[[id]] <- samples
    rownames(tmp) <- tmp$OTU.ID
    missing_otu_ids <- setdiff(taxonomy[[1]], tmp$OTU.ID)
    if (length(missing_otu_ids) > 0) {
      to_add <- data.frame(
        OTU.ID = missing_otu_ids,
        matrix(0, nrow = length(missing_otu_ids), ncol = length(samples)),
        row.names = missing_otu_ids,
        stringsAsFactors = FALSE
      )
      colnames(to_add) <- c("OTU.ID", samples)
      tmp <- rbind(tmp, to_add)
    }
    tmp <- tmp[taxonomy[[1]], , drop = FALSE]
    tmp
  })
  for (i in seq_along(asv_data)) {
    if (i == 1) {
      next
    }
    asv_data[[i]] <- asv_data[[i]][, -1, drop = FALSE] # Remove OTU.ID column from subsequent datasets
  }
  asv_data <- do.call(cbind, asv_data)
  if (nrow(asv_data) == 0) {
    stop("No ASV data found in the specified files.")
  }
  rownames(asv_data) <- NULL
  zero_rows <- apply(asv_data[,-1], 1, function(x)(all(x == 0)))
  if (any(zero_rows)) {
    asv_data <- asv_data[!zero_rows, , drop = FALSE]
    taxonomy <- taxonomy[taxonomy$Feature.ID %in% asv_data$OTU.ID, , drop = FALSE]
  }
  return(list(
    asv_data = asv_data,
    taxonomy = taxonomy,
    samples_by_dataset = samples_by_dataset
  ))
}

#' Merge metadata tables from multiple datasets
#' @param config_data Configuration data containing dataset information
#' @param samples_by_dataset List of samples for each dataset
merge_metadata_tables <- function (config_data, samples_by_dataset) {
  metadata_info <- prepare_metadata_by_dataset(config_data)
  metadata_files <- sapply(config_data$datasets, function (x)(x$files$metadata))
  if (is.null(metadata_files) || length(metadata_files) == 0) {
    stop("No metadata files specified in the configuration.")
  }
  metadata_data <- lapply(names(metadata_files), function(id) {
    meta <- read_metadata_safe(metadata_files[[id]])
    meta <- meta[meta$sample_id %in% samples_by_dataset[[id]], , drop = FALSE]
    if (nrow(meta) == 0) {
      stop(paste("No samples found in dataset", id, "for metadata."))
    }
    meta <- meta[, metadata_info$select_by_dataset[[id]], drop = FALSE]
    if (length(metadata_info$add_by_dataset[[id]]) > 0) {
      for (name in names(metadata_info$add_by_dataset[[id]])) {
        meta[[name]] <- metadata_info$add_by_dataset[[id]][[name]]
      }
    }
    if (length(metadata_info$rename_by_dataset[[id]]) > 0) {
      cn <- colnames(meta)
      colnames_to_rename <- which(cn %in% names(metadata_info$rename_by_dataset[[id]]))
      cn[colnames_to_rename] <- unname(metadata_info$rename_by_dataset[[id]][cn[colnames_to_rename]])
      colnames(meta) <- cn
    }
    meta <- meta[, metadata_info$final_selection, drop = FALSE]
    if (ncol(meta) == 0) {
      stop(paste("No valid metadata columns found in dataset", id, "for metadata."))
    }
    meta
  })
  metadata_data <- do.call(rbind, metadata_data)
  if (nrow(metadata_data) == 0) {
    stop("No metadata data found in the specified files.")
  }
  
  # Ensure sample IDs are trimmed
  metadata_data$sample_id <- trimws(metadata_data$sample_id)
  
  # Filter samples based on the samples_by_dataset
  valid_samples <- unlist(samples_by_dataset)
  metadata_data <- metadata_data[metadata_data$sample_id %in% valid_samples, , drop = FALSE]
  
  return(metadata_data)
}

#' Merge PICRUSt tables from multiple datasets
#' @param config_data Configuration data containing dataset information
#' @param type Type of PICRUSt data to merge (e.g., "picrust.ko", "picrust.pathways", "picrust.ec")
#' @param samples_by_dataset List of samples for each dataset
merge_picrust_tables <- function (config_data, type, samples_by_dataset) {
  picrust_files <- sapply(config_data$datasets, function (x)(x$files[[type]]))
  if (is.null(picrust_files) || length(picrust_files) == 0) {
    stop("No PICRUSt files specified in the configuration.")
  }
  
  picrust_data <- lapply(names(picrust_files), function(id) {
    tmp <- read_tsv(picrust_files[[id]])
    class(tmp) <- "data.frame"
    keep <- colnames(tmp)[1:2]
    samples <- intersect(samples_by_dataset[[id]], colnames(tmp))
    if (length(samples) == 0) {
      stop(paste("No samples found in dataset", id, "for PICRUSt table."))
    }
    tmp <- tmp[, c(keep, samples)]
    rownames(tmp) <- tmp[[1]]
    tmp
  })
  
  all_rows <- unique(do.call(rbind, lapply(picrust_data, function(x)(x[, 1:2]))))
  
  picrust_data <- lapply(picrust_data, function(x) {
    found_rows <- intersect(all_rows[[1]], x[[1]])
    missing_rows <- setdiff(all_rows[[1]], found_rows)
    if (length(missing_rows) > 0) {
      to_add <- data.frame(
        all_rows[all_rows[[1]] %in% missing_rows, , drop = FALSE],
        matrix(0, nrow = length(missing_rows), ncol = length(samples_by_dataset[[id]])),
        row.names = missing_rows,
        stringsAsFactors = FALSE
      )
      colnames(to_add) <- c(colnames(all_rows), samples_by_dataset[[id]])
      x <- rbind(x, to_add)
    }
    x <- x[all_rows[[1]], , drop = FALSE]
    x
  })
  for (i in seq_along(picrust_data)) {
    if (i == 1) {
      next
    }
    picrust_data[[i]] <- picrust_data[[i]][, -(1:2), drop = FALSE] # Remove the feature and description columns from subsequent datasets
  }
  picrust_data <- do.call(cbind, picrust_data)
  if (nrow(picrust_data) == 0) {
    stop("No PICRUSt data found in the specified files.")
  }
  
  return(picrust_data)
}

merge_qza_files <- function (config_data, type, samples_by_dataset) {
  input_files <- sapply(config_data$datasets, function (x)(x$files[[type]]))
  sample_names <- unname(unlist(samples_by_dataset))
  qza_type <- detect_qza_type(input_files[1])
  message(sprintf("Detected QZA file type: %s", qza_type))
  .validate_file_type_consistency(input_files, qza_type)
  # Perform merge operation
  merged_data <- switch(
    qza_type,
    "alpha" = merge_alpha_diversity(input_files, sample_names),
    "beta" = merge_beta_diversity(input_files, sample_names),
    stop(sprintf("Unsupported file type: %s", qza_type))
  )
  write_merged_qza(merged_data, config_data$outputFiles[[type]], qza_type, input_files)
}

# =============================================================================
# QZA MERGE FUNCTIONS
# =============================================================================

#' Merge alpha diversity files
#'
#' @param qza_files character vector of paths to QZA files
#' @param sample_names character vector of sample names to filter (optional)
#' @return data.frame merged alpha diversity data
merge_alpha_diversity <- function(qza_files, sample_names = NULL) {
  merged_data <- data.frame()
  
  for (file_path in qza_files) {
    message(sprintf("Processing alpha diversity file: %s", file_path))
    
    # Read and validate QZA file
    qza_data <- tryCatch(
      {
        read_qza(file_path)
      },
      error = function(e) {
        stop(sprintf("Failed to read QZA file '%s': %s", file_path, e$message))
      }
    )
    
    alpha_data <- qza_data$data
    
    # Convert to standardized data frame format
    alpha_df <- .convert_alpha_to_dataframe(alpha_data)
    
    # Filter by sample names if provided
    if (!is.null(sample_names) && length(sample_names) > 0) {
      alpha_df <- alpha_df[rownames(alpha_df) %in% sample_names, , drop = FALSE]
    }
    
    # Merge with existing data
    merged_data <- .merge_alpha_dataframes(merged_data, alpha_df)
  }
  
  # Sort by sample names for consistency
  merged_data <- merged_data[order(rownames(merged_data)), , drop = FALSE]
  
  message(sprintf("Successfully merged %d alpha diversity samples", nrow(merged_data)))
  return(merged_data)
}

#' Convert alpha diversity data to standardized data frame
#'
#' @param alpha_data various formats of alpha diversity data
#' @return data.frame standardized alpha diversity data frame
.convert_alpha_to_dataframe <- function(alpha_data) {
  if (is.vector(alpha_data)) {
    alpha_df <- data.frame(
      alpha_diversity = as.numeric(alpha_data),
      stringsAsFactors = FALSE
    )
    rownames(alpha_df) <- names(alpha_data)
  } else if (is.data.frame(alpha_data)) {
    alpha_df <- alpha_data
    if (ncol(alpha_df) == 2) {
      alpha_df <- data.frame(
        alpha_diversity = as.numeric(alpha_df[[2]]),
        row.names = as.character(alpha_df[[1]]),
        stringsAsFactors = FALSE
      )
    }
  } else {
    stop("Unsupported alpha diversity data format")
  }
  
  return(alpha_df)
}

#' Merge two alpha diversity data frames
#'
#' @param existing data.frame existing merged data
#' @param new_data data.frame new data to merge
#' @return data.frame merged data frame
.merge_alpha_dataframes <- function(existing, new_data) {
  if (nrow(existing) == 0) {
    return(new_data)
  }
  
  # Check for duplicate samples
  common_samples <- intersect(rownames(existing), rownames(new_data))
  if (length(common_samples) > 0) {
    warning(sprintf("Found %d duplicate samples. Keeping first occurrence.", length(common_samples)))
    new_data <- new_data[!rownames(new_data) %in% common_samples, , drop = FALSE]
  }
  
  # Validate column compatibility
  if (ncol(existing) != ncol(new_data) || !all(colnames(existing) == colnames(new_data))) {
    if (ncol(existing) == ncol(new_data) && !all(colnames(existing) == colnames(new_data))) {
      colnames(new_data) <- colnames(existing)
    } else {
      stop("Column names or structure differ between files. All files must have the same alpha diversity metric.")
    }
  }
  
  return(rbind(existing, new_data))
}

#' Merge beta diversity files (PCoA results)
#'
#' @param qza_files character vector of paths to QZA files
#' @param sample_names character vector of sample names to filter (optional)
#' @return list merged beta diversity data with Vectors and ProportionExplained
merge_beta_diversity <- function(qza_files, sample_names = NULL) {
  merged_vectors <- data.frame()
  merged_proportion_explained <- numeric()
  all_pc_axes <- character()
  
  for (file_path in qza_files) {
    message(sprintf("Processing beta diversity file: %s", file_path))
    
    # Read and validate QZA file
    qza_data <- tryCatch(
      {
        read_qza(file_path)
      },
      error = function(e) {
        stop(sprintf("Failed to read QZA file '%s': %s", file_path, e$message))
      }
    )
    
    # Extract PCoA components
    pcoa_components <- .extract_pcoa_components(qza_data, file_path)
    vectors <- pcoa_components$vectors
    proportion_explained <- pcoa_components$proportion_explained
    
    # Standardize SampleID column
    vectors <- .standardize_sample_ids(vectors)
    
    # Filter by sample names if provided
    if (!is.null(sample_names) && length(sample_names) > 0) {
      vectors <- vectors %>% filter(SampleID %in% sample_names)
    }
    
    # Track PC axes for consistency across files
    pc_cols <- grep("^PC[0-9]+$", colnames(vectors), value = TRUE)
    all_pc_axes <- unique(c(all_pc_axes, pc_cols))
    
    # Merge with existing data
    merge_result <- .merge_beta_dataframes(merged_vectors, vectors, all_pc_axes)
    merged_vectors <- merge_result$vectors
    
    # Handle proportion explained (use first file's values as reference)
    if (!is.null(proportion_explained) && length(merged_proportion_explained) == 0) {
      merged_proportion_explained <- proportion_explained
    }
  }
  
  # Final data cleaning and organization
  result <- .finalize_beta_merge(merged_vectors, merged_proportion_explained, all_pc_axes)
  
  message(sprintf(
    "Successfully merged %d beta diversity samples with %d PC axes",
    nrow(result$Vectors), length(all_pc_axes)
  ))
  
  return(result)
}

#' Extract PCoA components from QZA data
#'
#' @param qza_data list QZA data structure
#' @param file_path character file path for error reporting
#' @return list with vectors and proportion_explained components
.extract_pcoa_components <- function(qza_data, file_path) {
  if (is.list(qza_data$data) && "Vectors" %in% names(qza_data$data)) {
    # Standard PCoA results structure
    vectors <- qza_data$data$Vectors
    proportion_explained <- qza_data$data$ProportionExplained
  } else if (is.data.frame(qza_data$data)) {
    # Direct data frame structure
    vectors <- qza_data$data
    proportion_explained <- NULL
  } else {
    stop(sprintf("Unsupported beta diversity data structure in file: %s", file_path))
  }
  
  return(list(vectors = vectors, proportion_explained = proportion_explained))
}

#' Standardize SampleID column in vectors data frame
#'
#' @param vectors data.frame PCoA vectors
#' @return data.frame vectors with standardized SampleID column
.standardize_sample_ids <- function(vectors) {
  if (!"SampleID" %in% colnames(vectors)) {
    sample_col_idx <- grep("^sample", colnames(vectors), ignore.case = TRUE)
    if (length(sample_col_idx) > 0) {
      colnames(vectors)[sample_col_idx[1]] <- "SampleID"
    } else {
      vectors <- vectors %>% rownames_to_column("SampleID")
    }
  }
  
  vectors$SampleID <- trimws(as.character(vectors$SampleID))
  return(vectors)
}

#' Merge beta diversity data frames
#'
#' @param existing data.frame existing merged vectors
#' @param new_vectors data.frame new vectors to merge
#' @param all_pc_axes character vector of all PC axes
#' @return list with merged vectors
.merge_beta_dataframes <- function(existing, new_vectors, all_pc_axes) {
  if (nrow(existing) == 0) {
    return(list(vectors = new_vectors))
  }
  
  # Ensure both data frames have all PC columns
  for (axis in all_pc_axes) {
    if (!axis %in% colnames(existing)) {
      existing[[axis]] <- 0
    }
    if (!axis %in% colnames(new_vectors)) {
      new_vectors[[axis]] <- 0
    }
  }
  
  # Reorder columns to match
  common_cols <- intersect(colnames(existing), colnames(new_vectors))
  existing <- existing[, common_cols, drop = FALSE]
  new_vectors <- new_vectors[, common_cols, drop = FALSE]
  
  # Combine data frames
  merged_vectors <- rbind(existing, new_vectors)
  
  return(list(vectors = merged_vectors))
}

#' Finalize beta diversity merge
#'
#' @param merged_vectors data.frame merged vectors
#' @param proportion_explained numeric vector of proportion explained values
#' @param all_pc_axes character vector of all PC axes
#' @return list final result structure
.finalize_beta_merge <- function(merged_vectors, proportion_explained, all_pc_axes) {
  # Remove duplicate samples (keep first occurrence)
  merged_vectors <- merged_vectors %>%
    distinct(SampleID, .keep_all = TRUE) %>%
    arrange(SampleID)
  
  # Order PC columns properly (PC1, PC2, PC3, etc.)
  pc_nums <- as.numeric(gsub("PC", "", all_pc_axes))
  pc_cols_ordered <- paste0("PC", sort(pc_nums))
  
  other_cols <- setdiff(colnames(merged_vectors), all_pc_axes)
  column_order <- c("SampleID", pc_cols_ordered, setdiff(other_cols, "SampleID"))
  merged_vectors <- merged_vectors[, column_order, drop = FALSE]
  
  # Prepare final result structure
  result <- list(Vectors = merged_vectors)
  
  if (length(proportion_explained) > 0) {
    # Ensure proportion explained matches the number of PC axes
    n_axes <- length(pc_cols_ordered)
    if (length(proportion_explained) < n_axes) {
      proportion_explained <- c(proportion_explained, rep(0, n_axes - length(proportion_explained)))
    } else if (length(proportion_explained) > n_axes) {
      proportion_explained <- proportion_explained[1:n_axes]
    }
    result$ProportionExplained <- proportion_explained
  }
  
  return(result)
}

# =============================================================================
# QZA WRITING FUNCTIONS
# =============================================================================

#' Write merged data to QZA file
#'
#' @param merged_data various merged data structure
#' @param output_file character path to output file
#' @param data_type character "alpha" or "beta"
#' @param input_files character vector of input file paths
#' @return invisible(TRUE) on success
write_merged_qza <- function(merged_data, output_file, data_type, input_files) {
  temp_dir <- tempdir()
  uuid <- generate_qiime_uuid()
  
  tryCatch(
    {
      artifact_dir <- create_artifact_structure(temp_dir, uuid)
      
      # Copy provenance from first input file
      .copy_provenance(input_files[1], artifact_dir, temp_dir, uuid)
      
      # Write data based on type
      metadata_content <- switch(data_type,
                                 "alpha" = .write_alpha_data(merged_data, artifact_dir, uuid),
                                 "beta" = .write_beta_data(merged_data, artifact_dir, uuid),
                                 stop(sprintf("Unsupported data type: %s", data_type))
      )
      
      # Write metadata and create QZA file
      .finalize_qza_creation(artifact_dir, metadata_content, output_file, temp_dir, uuid)
      
      message(sprintf("Merged QZA file written to: %s", output_file))
    },
    error = function(e) {
      # Clean up on error
      unlink(file.path(temp_dir, uuid), recursive = TRUE)
      stop(sprintf("Failed to write QZA file: %s", e$message))
    }
  )
  
  return(invisible(TRUE))
}

#' Copy provenance from input file
#'
#' @param input_file character path to input file
#' @param artifact_dir character path to artifact directory
#' @param temp_dir character path to temporary directory
#' @param uuid character UUID for artifact
.copy_provenance <- function(input_file, artifact_dir, temp_dir, uuid) {
  message("Copying provenance from first input file...")
  
  first_file_temp <- file.path(temp_dir, "first_input_extract")
  dir.create(first_file_temp, recursive = TRUE, showWarnings = FALSE)
  
  # Extract input file
  old_wd <- getwd()
  on.exit(setwd(old_wd), add = TRUE)
  
  setwd(first_file_temp)
  zip_result <- system2("unzip", c("-q", shQuote(input_file)), stdout = FALSE, stderr = FALSE)
  setwd(old_wd)
  
  if (zip_result != 0) {
    warning("Failed to extract provenance from input file")
    .create_minimal_provenance(artifact_dir, uuid)
  } else {
    extracted_dirs <- list.dirs(first_file_temp, recursive = FALSE)
    if (length(extracted_dirs) > 0) {
      provenance_source <- file.path(extracted_dirs[1], "provenance")
      if (dir.exists(provenance_source)) {
        file.copy(provenance_source, artifact_dir, recursive = TRUE)
      } else {
        .create_minimal_provenance(artifact_dir, uuid)
      }
    } else {
      .create_minimal_provenance(artifact_dir, uuid)
    }
  }
  
  # Clean up extraction directory
  unlink(first_file_temp, recursive = TRUE)
}

#' Create minimal provenance structure
#'
#' @param artifact_dir character path to artifact directory
#' @param uuid character UUID for artifact
.create_minimal_provenance <- function(artifact_dir, uuid) {
  provenance_dir <- file.path(artifact_dir, "provenance")
  dir.create(provenance_dir, recursive = TRUE, showWarnings = FALSE)
  
  writeLines(QIIME_VERSION, file.path(provenance_dir, "VERSION"))
  
  provenance_metadata <- sprintf(
    "uuid: %s\ntype: NoProvenanceMetadata\nformat: NoProvenanceMetadataFormat\n",
    uuid
  )
  writeLines(provenance_metadata, file.path(provenance_dir, "metadata.yaml"))
}

#' Write alpha diversity data
#'
#' @param merged_data data.frame alpha diversity data
#' @param artifact_dir character path to artifact directory
#' @param uuid character UUID for artifact
#' @return character metadata content
.write_alpha_data <- function(merged_data, artifact_dir, uuid) {
  data_file <- file.path(artifact_dir, "data", "alpha-diversity.tsv")
  
  write.table(
    merged_data,
    data_file,
    sep = "\t",
    quote = FALSE,
    col.names = TRUE,
    row.names = TRUE
  )
  
  return(sprintf(
    "uuid: %s\ntype: SampleData[AlphaDiversity]\nformat: AlphaDiversityDirectoryFormat\n",
    uuid
  ))
}

#' Write beta diversity data
#'
#' @param merged_data list beta diversity data with Vectors and ProportionExplained
#' @param artifact_dir character path to artifact directory
#' @param uuid character UUID for artifact
#' @return character metadata content
.write_beta_data <- function(merged_data, artifact_dir, uuid) {
  # Write ordination.txt file
  .write_ordination_file(merged_data, artifact_dir)
  
  # Write additional TSV files for compatibility
  write_tsv(
    merged_data$Vectors,
    file.path(artifact_dir, "data", "sample-coordinates.tsv")
  )
  
  if ("ProportionExplained" %in% names(merged_data)) {
    prop_df <- data.frame(
      Axis = paste0("PC", seq_along(merged_data$ProportionExplained)),
      ProportionExplained = merged_data$ProportionExplained
    )
    write_tsv(prop_df, file.path(artifact_dir, "data", "proportion-explained.tsv"))
  }
  
  return(sprintf(
    "uuid: %s\ntype: PCoAResults\nformat: OrdinationDirectoryFormat\n",
    uuid
  ))
}

#' Write ordination.txt file in QIIME2 format
#'
#' @param merged_data list beta diversity data
#' @param artifact_dir character path to artifact directory
.write_ordination_file <- function(merged_data, artifact_dir) {
  ordination_file <- file.path(artifact_dir, "data", "ordination.txt")
  vectors <- merged_data$Vectors
  pc_cols <- grep("^PC[0-9]+$", colnames(vectors), value = TRUE)
  n_axes <- length(pc_cols)
  
  ordination_lines <- character()
  
  # Eigenvalues section
  if ("ProportionExplained" %in% names(merged_data)) {
    prop_explained <- merged_data$ProportionExplained
    if (length(prop_explained) != n_axes) {
      prop_explained <- c(prop_explained, rep(0, n_axes))[1:n_axes]
    }
  } else {
    prop_explained <- rep(1 / n_axes, n_axes)
  }
  
  ordination_lines <- c(
    ordination_lines,
    paste("Eigvals", n_axes, sep = "\t"),
    paste(prop_explained, collapse = "\t"),
    ""
  )
  
  # Proportion explained section
  ordination_lines <- c(
    ordination_lines,
    paste("Proportion explained", n_axes, sep = "\t"),
    paste(prop_explained, collapse = "\t"),
    ""
  )
  
  # Species section (empty for beta diversity)
  ordination_lines <- c(ordination_lines, "Species\t0\t0", "")
  
  # Site section (sample coordinates)
  ordination_lines <- c(
    ordination_lines,
    paste("Site", nrow(vectors), n_axes, sep = "\t")
  )
  
  for (i in seq_len(nrow(vectors))) {
    sample_id <- vectors$SampleID[i]
    coords <- as.numeric(vectors[i, pc_cols])
    site_line <- paste(c(sample_id, coords), collapse = "\t")
    ordination_lines <- c(ordination_lines, site_line)
  }
  
  # Site constraints section (empty)
  ordination_lines <- c(ordination_lines, "Site constraints\t0\t0")
  
  writeLines(ordination_lines, ordination_file)
}

#' Finalize QZA file creation
#'
#' @param artifact_dir character path to artifact directory
#' @param metadata_content character metadata content
#' @param output_file character path to output file
#' @param temp_dir character path to temporary directory
#' @param uuid character UUID for artifact
.finalize_qza_creation <- function(artifact_dir, metadata_content, output_file, temp_dir, uuid) {
  # Write metadata files
  writeLines(metadata_content, file.path(artifact_dir, "metadata.yaml"))
  writeLines(QIIME_VERSION, file.path(artifact_dir, "VERSION"))
  
  # Calculate and write checksums
  .write_checksums(artifact_dir)
  
  # Create QZA file (zip archive)
  .create_zip_archive(temp_dir, uuid, output_file)
  
  # Verify file creation
  if (!file.exists(output_file)) {
    stop(sprintf("Failed to create output file: %s", output_file))
  }
  
  # Clean up temporary files
  unlink(artifact_dir, recursive = TRUE)
}

#' Write checksums file
#'
#' @param artifact_dir character path to artifact directory
.write_checksums <- function(artifact_dir) {
  checksums_file <- file.path(artifact_dir, "checksums.md5")
  all_files <- list.files(artifact_dir, recursive = TRUE, full.names = TRUE)
  
  checksums <- character()
  
  for (file_path in all_files) {
    if (!file.info(file_path)$isdir) {
      relative_path <- sub(paste0(artifact_dir, "/"), "", file_path)
      checksum <- tools::md5sum(file_path)
      checksums <- c(checksums, paste(checksum, relative_path, sep = "  "))
    }
  }
  
  writeLines(checksums, checksums_file)
}

#' Create zip archive for QZA file
#'
#' @param temp_dir character path to temporary directory
#' @param uuid character UUID for artifact
#' @param output_file character path to output file
.create_zip_archive <- function(temp_dir, uuid, output_file) {
  old_wd <- getwd()
  on.exit(setwd(old_wd), add = TRUE)
  
  setwd(temp_dir)
  
  # Create absolute path for output file
  if (!startsWith(output_file, "/")) {
    output_file <- file.path(old_wd, output_file)
  }
  
  # Create zip archive
  zip_result <- system2("zip", c("-r", shQuote(output_file), shQuote(uuid)),
                        stdout = FALSE, stderr = FALSE
  )
  
  if (zip_result != 0) {
    stop(sprintf("Failed to create zip archive (exit code: %d)", zip_result))
  }
}

# =============================================================================
# QZA TYPE DETECTION
# =============================================================================
#' Detect QZA file type
#'
#' @param qza_file character path to QZA file
#' @return character "alpha" or "beta"
detect_qza_type <- function(qza_file) {
  qza_data <- tryCatch(
    {
      read_qza(qza_file)
    },
    error = function(e) {
      stop(sprintf("Failed to read QZA file '%s': %s", qza_file, e$message))
    }
  )
  
  qza_type <- qza_data$type
  
  if (grepl("AlphaDiversity", qza_type, ignore.case = TRUE)) {
    return("alpha")
  } else if (grepl("PCoA|Ordination|BetaDiversity", qza_type, ignore.case = TRUE)) {
    # Additional validation for beta diversity
    if (grepl("DistanceMatrix", qza_type, ignore.case = TRUE)) {
      stop("Distance matrix files are not supported. Please use PCoA results instead.")
    }
    
    .validate_beta_structure(qza_data)
    return("beta")
  } else {
    stop(sprintf(
      "Unsupported QZA file type: %s\nSupported types: AlphaDiversity, PCoAResults, Ordination",
      qza_type
    ))
  }
}

#' Validate beta diversity data structure
#'
#' @param qza_data list QZA data structure
.validate_beta_structure <- function(qza_data) {
  if (is.list(qza_data$data)) {
    if ("Vectors" %in% names(qza_data$data) || "ProportionExplained" %in% names(qza_data$data)) {
      return(TRUE)
    }
    
    # Check for distance matrix (not supported)
    if (is.matrix(qza_data$data) ||
        (is.data.frame(qza_data$data) && nrow(qza_data$data) == ncol(qza_data$data))) {
      stop("Distance matrix files are not supported. Please use PCoA results instead.")
    }
  } else if (is.data.frame(qza_data$data)) {
    pc_cols <- grep("^PC[0-9]+$", colnames(qza_data$data), value = TRUE)
    if (length(pc_cols) > 0) {
      return(TRUE)
    }
  }
  
  return(TRUE)
}

#' Validate that all files are of the same type
#'
#' @param input_files character vector of file paths
#' @param expected_type character expected file type
.validate_file_type_consistency <- function(input_files, expected_type) {
  for (file_path in input_files[-1]) {
    file_type <- detect_qza_type(file_path)
    if (file_type != expected_type) {
      stop(sprintf(
        "File type mismatch: expected '%s' but file '%s' is type '%s'",
        expected_type, file_path, file_type
      ))
    }
  }
}
# =============================================================================
# MAIN SCRIPT EXECUTION
# =============================================================================

# Parse command line arguments
if (!interactive()) {
  load_packages("optparse")
  
  option_list <- list(
    make_option("--config_file",
                type = "character",
                help = "Config file"
    )
  )
  
  parser <- OptionParser(option_list = option_list)
  args <- parse_args(parser)
  
  # Execute based on method
  config_file <- args$config_file
  validate_file(config_file, "Configuration file")
  tryCatch(
    {
      message("Reading configuration file...")
      config_data <- jsonlite::read_json(config_file)
      
      message("Extracting samples from configuration...")
      samples_by_dataset <- extract_samples_from_config(config_data)
      
      message("Merging taxonomy data...")
      taxonomy <- merge_taxonomy_data(config_data)
      
      message("Merging ASV tables and updating samples and taxonomy...")
      tmp <- merge_asv_tables(
        config_data = config_data,
        samples_by_dataset = samples_by_dataset,
        taxonomy = taxonomy
      )
      
      asv_data <- tmp$asv_data
      taxonomy <- tmp$taxonomy
      samples_by_dataset <- tmp$samples_by_dataset
      
      message("Writing merged ASV table and taxonomy data...")
      write_tsv_safe(asv_data, config_data$outputFiles$asvTable)
      write_tsv_safe(taxonomy, config_data$outputFiles$taxonomy)
      
      rm(tmp)
      
      message("Merging metadata tables...")
      metadata <- merge_metadata_tables(
        config_data = config_data,
        samples_by_dataset = samples_by_dataset
      )
      
      message("Writing merged metadata...")
      write_tsv_safe(metadata, config_data$outputFiles$metadata)
      
      # Merge PICRUSt data for each type
      for (type in PICRUST_TYPES) {
        if (!type %in% names(config_data$outputFiles)) {
          next
        }
        
        message(sprintf("Merging PICRUSt data for type: %s", type))
        picrust_data <- merge_picrust_tables(
          config_data = config_data,
          type = type,
          samples_by_dataset = samples_by_dataset
        )
        
        message(sprintf("Writing merged PICRUSt data for type: %s", type))
        write_tsv_safe(picrust_data, config_data$outputFiles[[type]], 
                       use_readr = TRUE)
      }
      
      # Merge QZA files for alpha and beta diversity
      for (type in names(config_data$outputFiles)) {
        if (type %in% c("metadata", "asvTable", "taxonomy") ||
            type %in% PICRUST_TYPES) {
          next
        }
        merge_qza_files(
          config_data = config_data,
          type = type,
          samples_by_dataset = samples_by_dataset
        )
      }
    },
    error = function(e) {
      # print the error message and quit with error code 100
      cat("//---BEGIN ERROR---//\n")
      cat(e$message, "\n")
      cat("//---END ERROR---//\n")
      quit(save = "no", status = 100)
    }
  )
  
  quit(save = "no")
}
