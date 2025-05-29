#!/usr/bin/env Rscript

# ==============================================================================
# QIIME2 QZA File Merger
# ==============================================================================

# Load required libraries --------------------------------------------------
suppressWarnings(suppressPackageStartupMessages({
  library(qiime2R)
  library(jsonlite)
  library(dplyr)
  library(tibble)
  library(readr)
  library(optparse)
}))

# Constants -----------------------------------------------------------------
QIIME_VERSION <- "2025.5.28"
UUID_PATTERN <- "^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$"

# Utility functions ---------------------------------------------------------

#' Generate a valid QIIME2 UUID
#'
#' @return character A valid UUID in 8-4-4-4-12 format
generate_qiime_uuid <- function() {
  uuid_parts <- c(8, 4, 4, 4, 12)
  uuid_segments <- vapply(uuid_parts, function(n) {
    paste0(sample(c(letters[1:6], 0:9), n, replace = TRUE), collapse = "")
  }, character(1))

  return(paste(uuid_segments, collapse = "-"))
}

#' Validate input files exist
#'
#' @param files character vector of file paths
#' @return logical TRUE if all files exist, stops execution otherwise
validate_input_files <- function(files) {
  missing_files <- files[!file.exists(files)]
  if (length(missing_files) > 0) {
    stop(sprintf("Input file(s) not found: %s", paste(missing_files, collapse = ", ")))
  }
  return(TRUE)
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

# Core merging functions ----------------------------------------------------

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
    stop("Column names or structure differ between files. All files must have the same alpha diversity metric.")
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

# QZA writing functions -----------------------------------------------------

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

# File type detection -------------------------------------------------------

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

# Main execution function --------------------------------------------------

#' Main function for command-line execution
#'
#' @return invisible(TRUE) on successful completion
main <- function() {
  # Define command-line options
  option_list <- list(
    make_option(
      c("-i", "--input-files"),
      type = "character",
      default = NULL,
      help = "Comma-separated list of input QZA files",
      metavar = "FILE1,FILE2,..."
    ),
    make_option(
      c("-s", "--sample-list"),
      type = "character",
      default = NULL,
      help = "JSON file containing list of sample names [optional]",
      metavar = "FILE"
    ),
    make_option(
      c("-o", "--output"),
      type = "character",
      default = NULL,
      help = "Output QZA file path",
      metavar = "FILE"
    )
  )

  # Create option parser
  opt_parser <- OptionParser(
    option_list = option_list,
    description = paste(
      "QIIME2 QZA File Merger",
      "",
      "This script merges two or more QZA files of the same type",
      "(Alpha or Beta Diversity) based on an optional list of sample names.",
      sep = "\n"
    ),
    epilogue = paste(
      "",
      "Examples:",
      "  # Merge alpha diversity files",
      "  Rscript merge_qza.R -i alpha1.qza,alpha2.qza -o merged_alpha.qza",
      "",
      "  # Merge beta diversity files with sample filtering",
      "  Rscript merge_qza.R -i beta1.qza,beta2.qza -s samples.json -o merged_beta.qza",
      "",
      sep = "\n"
    )
  )

  # Parse command-line arguments
  opt <- parse_args(opt_parser)

  # Validate required arguments
  if (is.null(opt$`input-files`)) {
    message("Error: --input-files is required\n")
    print_help(opt_parser)
    quit(status = 1)
  }

  if (is.null(opt$output)) {
    message("Error: --output is required\n")
    print_help(opt_parser)
    quit(status = 1)
  }

  # Parse and validate input files
  input_files <- trimws(unlist(strsplit(opt$`input-files`, ",")))

  if (length(input_files) < 2) {
    stop("At least two input files must be specified")
  }

  validate_input_files(input_files)

  # Read sample list if provided
  sample_names <- NULL
  if (!is.null(opt$`sample-list`)) {
    sample_names <- .read_sample_list(opt$`sample-list`)
  }

  # Detect and validate file types
  qza_type <- detect_qza_type(input_files[1])
  message(sprintf("Detected QZA file type: %s", qza_type))

  .validate_file_type_consistency(input_files, qza_type)

  # Perform merge operation
  merged_data <- switch(qza_type,
    "alpha" = merge_alpha_diversity(input_files, sample_names),
    "beta" = merge_beta_diversity(input_files, sample_names),
    stop(sprintf("Unsupported file type: %s", qza_type))
  )

  # Write merged data to output file
  write_merged_qza(merged_data, opt$output, qza_type, input_files)

  message("Merge completed successfully!")
  return(invisible(TRUE))
}

# Helper functions for main ------------------------------------------------

#' Read sample list from JSON file
#'
#' @param sample_file character path to sample list file
#' @return character vector of sample names
.read_sample_list <- function(sample_file) {
  if (!file.exists(sample_file)) {
    stop(sprintf("Sample list file does not exist: %s", sample_file))
  }

  message(sprintf("Reading sample list from: %s", sample_file))

  sample_data <- tryCatch(
    {
      fromJSON(sample_file)
    },
    error = function(e) {
      stop(sprintf("Failed to parse JSON file '%s': %s", sample_file, e$message))
    }
  )

  # Handle different JSON structures
  if (is.list(sample_data) && "samples" %in% names(sample_data)) {
    sample_names <- sample_data$samples
  } else {
    sample_names <- sample_data
  }

  if (length(sample_names) == 0) {
    stop("No sample names found in sample list file")
  }

  message(sprintf("Found %d sample names", length(sample_names)))
  return(sample_names)
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

# Script execution ----------------------------------------------------------

# Run main function if script is executed directly
if (!interactive()) {
  main()
}
