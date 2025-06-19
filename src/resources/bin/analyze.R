#!/usr/bin/env Rscript

#' Microbiome Analysis Script
#'
#' This script provides various microbiome analysis functions including
#' alpha/beta diversity analysis, differential abundance analysis, and
#' visualization functions.
#'
#'
# =============================================================================
# CONSTANTS AND CONFIGURATION
# =============================================================================

TAXONOMY_LEVEL_LABELS <- c("d", "p", "c", "o", "f", "g", "s")
TAXONOMY_LEVELS <- c(
  "Domain", "Phylum", "Class", "Order", "Family", "Genus", "Species"
)
DEFAULT_PLOT_WIDTH <- 15
DEFAULT_PLOT_HEIGHT <- 11
DEFAULT_DPI <- 300
DEFAULT_DEVICE <- "svg"

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

#' Validate taxonomy level
#' @param level Integer taxonomy level (1-7)
validate_taxonomy_level <- function(level) {
  if (is.null(level) || level < 1 || level > 7) {
    stop("Taxonomy level must be between 1 and 7")
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

read_cacheable_file <- function(filepath, reader, reader_args = list()) {
  hashed_args_for_filename <- digest::digest(reader_args)
  cache_filepath <- file.path(
    dirname(filepath),
    paste0(basename(filepath), "_", hashed_args_for_filename, ".rds")
  )
  if (file.exists(cache_filepath)) {
    return(readRDS(cache_filepath))
  }
  result <- do.call(reader, reader_args)
  saveRDS(result, cache_filepath)
  result
}

# =============================================================================
# DIVERSITY ANALYSIS FUNCTIONS
# =============================================================================

#' Create alpha diversity boxplot with statistical comparisons
#' @param diversity_file Path to QIIME2 alpha diversity artifact
#' @param metadata_file Path to metadata file
#' @param class_variable Column name for grouping variable
#' @param comparisons List of pairwise comparisons (optional)
#' @param output_file Path for output plot
alpha_diversity_plot <- function(diversity_file, metadata_file, class_variable,
                                 comparisons = NULL, output_file) {
  # Load required packages
  load_packages(c("qiime2R", "ggpubr", "dplyr", "tibble", "ggplot2"))

  # Validate inputs
  validate_file(diversity_file, "Alpha diversity file")

  # Validate comparisons format
  if (!is.null(comparisons)) {
    if (!is.list(comparisons)) {
      stop("Comparisons must be a list")
    }
    if (any(sapply(comparisons, length) != 2)) {
      stop("Each comparison must have exactly 2 groups")
    }
  }

  # Read and process data
  diversity <- read_qza(diversity_file)$data
  metadata <- read_metadata_safe(metadata_file)

  # Prepare diversity data
  colnames(diversity)[1] <- "alpha_diversity"
  diversity <- diversity %>%
    rownames_to_column("sample_id") %>%
    mutate(sample_id = trimws(sample_id))

  # Merge data
  plot_data <- metadata %>%
    left_join(diversity, by = "sample_id") %>%
    select(sample_id, alpha_diversity, all_of(class_variable)) %>%
    na.omit()

  # Validate class variable
  if (!class_variable %in% colnames(plot_data)) {
    stop(paste("Variable", class_variable, "not found in metadata"))
  }

  # Create plot
  p <- ggboxplot(
    plot_data,
    x = class_variable,
    y = "alpha_diversity",
    color = class_variable,
    legend = "none",
    ylab = "Alpha Diversity",
    xlab = class_variable,
    add = "jitter"
  ) + theme_minimal()

  # Add statistical comparisons if provided
  if (!is.null(comparisons) && !all(is.na(max(plot_data$alpha_diversity)))) {
    p <- p +
      stat_compare_means(comparisons = comparisons, method = "t.test") +
      stat_compare_means(
        label.y = max(plot_data$alpha_diversity) * 1.20,
        method = "anova"
      )
  }

  # Save plot
  save_plot(p, output_file)
}

#' Create beta diversity PCoA plot
#' @param diversity_file Path to QIIME2 beta diversity artifact
#' @param metadata_file Path to metadata file
#' @param color_var Column name for coloring points
#' @param output_file Path for output plot
beta_diversity_plot <- function(diversity_file, metadata_file, color_var, output_file) {
  # Load required packages
  load_packages(c("qiime2R", "ggplot2", "dplyr", "ggforce"))

  # Validate inputs
  validate_file(diversity_file, "Beta diversity file")

  # Read data
  metadata <- read_metadata_safe(metadata_file)
  colnames(metadata)[1] <- "SampleID"

  # Validate color variable
  if (!color_var %in% colnames(metadata)) {
    stop(paste("Variable", color_var, "not found in metadata"))
  }

  # Process color variable
  if (is.character(metadata[[color_var]])) {
    metadata[[color_var]] <- factor(metadata[[color_var]])
  }

  # Read diversity data
  diversity <- read_qza(diversity_file)
  pc1_explain <- round(100 * diversity$data$ProportionExplained[1], 2)
  pc2_explain <- round(100 * diversity$data$ProportionExplained[2], 2)

  # Create plot
  p <- diversity$data$Vectors %>%
    select(SampleID, PC1, PC2) %>%
    left_join(metadata, by = "SampleID") %>%
    select(PC1, PC2, all_of(color_var)) %>%
    na.omit() %>%
    ggplot(aes(x = PC1, y = PC2)) +
    geom_point(alpha = 0.5, size = 5, aes(colour = .data[[color_var]])) +
    xlab(paste("PC1:", pc1_explain, "%")) +
    ylab(paste("PC2:", pc2_explain, "%")) +
    theme_minimal() +
    stat_ellipse(aes(colour = .data[[color_var]]), level = 0.95)

  # Save plot
  save_plot(p, output_file, width = 15, height = 10)
}

# =============================================================================
# ABUNDANCE ANALYSIS FUNCTIONS
# =============================================================================

#' Convert ASV table to OTU table at specified taxonomic level
#' @param asv_file Path to ASV table
#' @param taxonomy_file Path to taxonomy file
#' @param taxonomy_level Taxonomic level (1-7)
#' @return Data frame with aggregated OTU counts
convert_asv_to_otu_table <- function(asv_file, taxonomy_file, taxonomy_level) {
  load_packages(c("dplyr", "tibble"))

  # Validate inputs
  validate_file(asv_file, "ASV file")
  validate_file(taxonomy_file, "Taxonomy file")
  validate_taxonomy_level(taxonomy_level)

  # Read files
  asv_table <- read.table(
    asv_file,
    sep = "\t", header = TRUE, stringsAsFactors = FALSE,
    row.names = 1, check.names = FALSE
  )

  taxonomy <- read.table(
    taxonomy_file,
    sep = "\t", header = TRUE, stringsAsFactors = FALSE
  )

  # Process taxonomy
  taxonomy_parsed <- parse_taxonomy_string(taxonomy$Taxon, taxonomy_level)
  taxonomy$TaxonAtLevel <- taxonomy_parsed

  # Aggregate at taxonomic level
  otu_table <- asv_table %>%
    rownames_to_column("Feature.ID") %>%
    left_join(taxonomy, by = "Feature.ID") %>%
    select(-Feature.ID, -Taxon, -Confidence) %>%
    group_by(TaxonAtLevel) %>%
    summarise(across(everything(), sum), .groups = "drop") %>%
    relocate(TaxonAtLevel, .before = 1)

  colnames(otu_table)[1] <- "OTU.ID"
  as.data.frame(otu_table)
}

#' Parse taxonomy strings to specified level
#' @param taxonomy_strings Vector of taxonomy strings
#' @param level Taxonomic level to extract
#' @return Character vector of parsed taxonomy
parse_taxonomy_string <- function(taxonomy_strings, level) {
  taxonomy_parsed <- lapply(strsplit(taxonomy_strings, ";\\s*"), function(x) {
    x <- strsplit(x, "__")
    x <- setNames(sapply(x, function(y) y[2]), sapply(x, function(y) y[1]))
    x <- x[TAXONOMY_LEVEL_LABELS][seq_len(level)]
    if (length(which(!is.na(x) & x != "")) == 0) {
      return("Unknown")
    }
    x[is.na(x) | x == ""] <- "Unknown"
    names(x) <- TAXONOMY_LEVEL_LABELS[seq_len(level)]
    paste(names(x), x, sep = "__", collapse = ";")
  })
  unlist(taxonomy_parsed)
}

# =============================================================================
# DIFFERENTIAL ABUNDANCE ANALYSIS
# =============================================================================

#' Prepare phyloseq object for DESeq2 analysis
#' @param asv_file Path to ASV table
#' @param taxonomy_file Path to taxonomy file
#' @param metadata_file Path to metadata file
#' @param taxonomy_level Taxonomic level
#' @param class_variable Grouping variable
#' @param group1 First group for comparison
#' @param group2 Second group for comparison
#' @return phyloseq object
prepare_deseq_data <- function(asv_file, taxonomy_file, metadata_file,
                               taxonomy_level, class_variable, group1, group2) {
  load_packages(c("phyloseq"))

  # Convert ASV to OTU table
  otu_data <- read_cacheable_file(
    asv_file,
    function(taxonomy_file, taxonomy_level) {
      convert_asv_to_otu_table(asv_file, taxonomy_file, taxonomy_level)
    },
    list(taxonomy_file, taxonomy_level)
  )
  # otu_data <- convert_asv_to_otu_table(asv_file, taxonomy_file, taxonomy_level)

  # Prepare taxonomy table
  otu_data$ID <- paste0("Taxa", seq_len(nrow(otu_data)))
  taxa_table <- otu_data[, c("OTU.ID", "ID")]
  rownames(otu_data) <- otu_data$ID
  otu_data <- otu_data[, !colnames(otu_data) %in% c("OTU.ID", "ID")]

  # Prepare metadata
  metadata <- read_metadata_safe(metadata_file)
  rownames(metadata) <- metadata$sample_id
  metadata <- metadata[, !colnames(metadata) %in% "sample_id", drop = FALSE]

  # Filter for comparison groups
  sample_data_obj <- sample_data(metadata)
  sample_data_obj <- sample_data_obj[
    sample_data_obj[[class_variable]] %in% c(group1, group2)
  ]

  # Match samples between OTU table and metadata
  common_samples <- intersect(colnames(otu_data), rownames(sample_data_obj))
  otu_data <- otu_data[, common_samples]
  sample_data_obj <- sample_data_obj[common_samples, ]

  # Create phyloseq object
  rownames(taxa_table) <- taxa_table$ID
  taxa_table <- as.matrix(taxa_table[, "OTU.ID", drop = FALSE])

  otu_table_obj <- otu_table(otu_data, taxa_are_rows = TRUE)
  tax_table_obj <- tax_table(taxa_table)

  phyloseq_obj <- merge_phyloseq(otu_table_obj, tax_table_obj, sample_data_obj)

  # Filter low abundance features
  phyloseq_obj <- prune_samples(sample_sums(phyloseq_obj) > 1, phyloseq_obj)
  phyloseq_obj <- prune_taxa(taxa_sums(phyloseq_obj) > 1, phyloseq_obj)

  phyloseq_obj
}

#' Run DESeq2 differential abundance analysis
#' @param asv_file Path to ASV table
#' @param taxonomy_file Path to taxonomy file
#' @param metadata_file Path to metadata file
#' @param taxonomy_level Taxonomic level
#' @param class_variable Grouping variable
#' @param group1 First group
#' @param group2 Second group
#' @param pv_threshold P-value threshold
#' @param fdr_threshold FDR threshold
#' @param output_prefix Output file prefix
#' @return List with results
run_deseq2_analysis <- function(asv_file, taxonomy_file, metadata_file,
                                taxonomy_level, class_variable, group1, group2,
                                pv_threshold, fdr_threshold, output_prefix) {
  load_packages(c("DESeq2", "phyloseq"))

  # Prepare data
  phyloseq_data <- prepare_deseq_data(
    asv_file, taxonomy_file, metadata_file,
    taxonomy_level, class_variable, group1, group2
  )

  # Run DESeq2
  formula_str <- as.formula(paste("~", class_variable))
  dds <- phyloseq_to_deseq2(phyloseq_data, formula_str)
  dds <- DESeq(dds, test = "Wald", fitType = "parametric")
  results_obj <- results(dds, contrast = c(class_variable, group1, group2))

  # Extract and save results
  results_list <- extract_deseq_results(
    results_obj, phyloseq_data,
    pv_threshold, fdr_threshold, output_prefix
  )

  results_list$phyloseq_data <- phyloseq_data
  saveRDS(results_list, paste0(output_prefix, "_raw.rds"))

  results_list
}

#' Extract DESeq2 results with filtering
#' @param results DESeq2 results object
#' @param phyloseq_data phyloseq object
#' @param pv_threshold P-value threshold
#' @param fdr_threshold FDR threshold
#' @param output_prefix Output file prefix
#' @return List with filtered results
extract_deseq_results <- function(results, phyloseq_data, pv_threshold,
                                  fdr_threshold, output_prefix) {
  # Helper function to extract results
  extract_filtered <- function(filter_condition) {
    filtered_indices <- which(filter_condition)
    if (length(filtered_indices) == 0) {
      return(NULL)
    }

    res_table <- results[filtered_indices, ]
    res_table <- cbind(
      as(res_table, "data.frame"),
      as(tax_table(phyloseq_data)[rownames(res_table), ], "matrix")
    )

    # Handle missing values
    res_table$log2FoldChange[is.na(res_table$log2FoldChange)] <- 0
    res_table$pvalue[is.na(res_table$pvalue)] <- 1
    res_table$padj[is.na(res_table$padj)] <- 1

    na.omit(res_table)
  }

  # Extract results with different filters
  results_all <- extract_filtered(results$pvalue <= 1)
  results_pv <- extract_filtered(results$pvalue < pv_threshold)
  results_fdr <- extract_filtered(results$padj < fdr_threshold)

  # Save results to files
  output_files <- list(
    all = paste0(output_prefix, "_all.tsv"),
    pvalue = paste0(output_prefix, "_pvalue.tsv"),
    fdr = paste0(output_prefix, "_fdr.tsv")
  )

  save_results_table(results_all, output_files$all)
  if (!is.null(results_pv)) save_results_table(results_pv, output_files$pvalue)
  if (!is.null(results_fdr)) save_results_table(results_fdr, output_files$fdr)

  list(
    all = results_all,
    pv_filtered = results_pv,
    fdr_filtered = results_fdr
  )
}

# =============================================================================
# VISUALIZATION FUNCTIONS
# =============================================================================

#' Save ggplot with standard settings
#' @param plot ggplot object
#' @param filename Output filename
#' @param width Plot width in inches
#' @param height Plot height in inches
#' @param dpi Resolution
#' @param device Output device
save_plot <- function(plot, filename, width = DEFAULT_PLOT_WIDTH,
                      height = DEFAULT_PLOT_HEIGHT, dpi = DEFAULT_DPI,
                      device = DEFAULT_DEVICE) {
  ggsave(
    filename = filename,
    plot = plot,
    bg = "transparent",
    width = width,
    height = height,
    dpi = dpi,
    units = "in",
    device = device
  )
}

#' Save results table to file
#' @param results_table Data frame to save
#' @param filename Output filename
save_results_table <- function(results_table, filename) {
  write.table(
    results_table,
    file = filename,
    sep = "\t",
    quote = FALSE,
    row.names = FALSE,
    col.names = TRUE,
    na = "NA"
  )
}

# =============================================================================
# PICRUST FUNCTIONAL ANALYSIS
# =============================================================================

#' Prepare phyloseq object for PICRUSt data
#' @param asv_file Path to PICRUSt output file
#' @param metadata_file Path to metadata file
#' @param class_variable Grouping variable
#' @param group1 First group for comparison
#' @param group2 Second group for comparison
#' @return phyloseq object
prepare_picrust_data <- function(asv_file, metadata_file, class_variable,
                                 group1, group2) {
  load_packages(c("readr", "phyloseq"))

  # Read PICRUSt data
  picrust_data <- read_tsv(asv_file, col_names = TRUE)
  picrust_data <- as.data.frame(picrust_data)
  colnames(picrust_data)[1] <- "ID"
  colnames(picrust_data)[2] <- "OTU.ID"

  # Prepare taxonomy table
  taxa_table <- picrust_data[, c("ID", "OTU.ID")]
  rownames(picrust_data) <- picrust_data$ID
  picrust_data <- picrust_data[, !colnames(picrust_data) %in% c("ID", "OTU.ID")]

  # Prepare metadata
  metadata <- read_metadata_safe(metadata_file)
  rownames(metadata) <- metadata$sample_id
  metadata <- metadata[, !colnames(metadata) %in% "sample_id", drop = FALSE]

  # Filter for comparison groups
  sample_data_obj <- sample_data(metadata)
  sample_data_obj <- sample_data_obj[
    sample_data_obj[[class_variable]] %in% c(group1, group2)
  ]

  # Match samples
  common_samples <- intersect(colnames(picrust_data), rownames(sample_data_obj))
  picrust_data <- picrust_data[, common_samples]
  sample_data_obj <- sample_data_obj[common_samples, ]

  # Create phyloseq object
  rownames(taxa_table) <- taxa_table$ID
  taxa_table <- as.matrix(taxa_table[, "OTU.ID", drop = FALSE])

  otu_table_obj <- otu_table(picrust_data, taxa_are_rows = TRUE)
  tax_table_obj <- tax_table(taxa_table)

  phyloseq_obj <- merge_phyloseq(otu_table_obj, tax_table_obj, sample_data_obj)

  # Filter low abundance features
  phyloseq_obj <- prune_samples(sample_sums(phyloseq_obj) > 1, phyloseq_obj)
  phyloseq_obj <- prune_taxa(taxa_sums(phyloseq_obj) > 1, phyloseq_obj)

  phyloseq_obj
}

#' Run DESeq2 analysis on PICRUSt data
#' @param asv_file Path to PICRUSt output file
#' @param metadata_file Path to metadata file
#' @param class_variable Grouping variable
#' @param group1 First group
#' @param group2 Second group
#' @param pv_threshold P-value threshold
#' @param fdr_threshold FDR threshold
#' @param output_prefix Output file prefix
#' @return List with results
run_picrust_analysis <- function(asv_file, metadata_file, class_variable,
                                 group1, group2, pv_threshold, fdr_threshold,
                                 output_prefix) {
  load_packages(c("DESeq2", "phyloseq"))

  # Prepare data
  phyloseq_data <- read_cacheable_file(
    asv_file,
    function(metadata_file, class_variable, group1, group2) {
      prepare_picrust_data(
        asv_file, metadata_file, class_variable, group1, group2
      )
    },
    list(metadata_file, class_variable, group1, group2)
  )
  # phyloseq_data <- prepare_picrust_data(
  #   asv_file, metadata_file, class_variable, group1, group2
  # )

  # Run DESeq2
  formula_str <- as.formula(paste("~", class_variable))
  dds <- phyloseq_to_deseq2(phyloseq_data, formula_str)
  dds <- DESeq(dds, test = "Wald", fitType = "parametric")
  results_obj <- results(dds, contrast = c(class_variable, group1, group2))

  # Extract and save results
  results_list <- extract_deseq_results(
    results_obj, phyloseq_data,
    pv_threshold, fdr_threshold, output_prefix
  )

  results_list$phyloseq_data <- phyloseq_data
  saveRDS(results_list, paste0(output_prefix, "_raw.rds"))

  results_list
}

# =============================================================================
# ABUNDANCE VISUALIZATION FUNCTIONS
# =============================================================================

#' Compute relative abundance data
#' @param asv_file Path to ASV table
#' @param taxonomy_file Path to taxonomy file
#' @param metadata_file Path to metadata file
#' @param taxonomy_level Taxonomic level
#' @param class_variable Grouping variable
#' @param hide_small Whether to group small abundances as "Others"
#' @return List with relative abundance data
compute_relative_abundance <- function(asv_file, taxonomy_file, metadata_file,
                                       taxonomy_level, class_variable,
                                       hide_small = FALSE) {
  load_packages(c("dplyr", "tidyr"))

  # Get OTU table
  otu_table <- read_cacheable_file(
    asv_file, function(taxonomy_file, taxonomy_level) {
      convert_asv_to_otu_table(asv_file, taxonomy_file, taxonomy_level)
    }, list(taxonomy_file, taxonomy_level)
  )
  # otu_table <- convert_asv_to_otu_table(asv_file, taxonomy_file, taxonomy_level)
  taxa <- otu_table$OTU.ID
  otu_table$OTU.ID <- NULL

  # Transpose for sample-wise analysis
  abund_df <- as.data.frame(t(otu_table))
  colnames(abund_df) <- taxa

  # Read metadata
  metadata <- read_metadata_safe(metadata_file)
  rownames(metadata) <- metadata$sample_id

  # Match samples
  common_samples <- intersect(metadata$sample_id, rownames(abund_df))
  abund_df <- abund_df[common_samples, ]
  metadata <- metadata[common_samples, ]

  # Add grouping variable
  abund_df$Group <- metadata[[class_variable]]
  abund_df <- na.omit(abund_df)

  # Calculate relative abundances
  rel_abund <- abund_df %>%
    group_by(Group) %>%
    summarise(across(everything(), sum), .groups = "drop")

  rel_abund[, -1] <- round(rel_abund[, -1] / rowSums(rel_abund[, -1]) * 100,
    digits = 2
  )

  rel_abund <- rel_abund %>%
    pivot_longer(cols = -Group, names_to = "Taxa", values_to = "value")

  rel_abund$Group <- as.character(rel_abund$Group)
  rel_abund_orig <- rel_abund

  # Group small abundances if requested
  if (hide_small) {
    small_abund <- rel_abund[rel_abund$value <= 1, ]
    rel_abund <- rel_abund[rel_abund$value > 1, ]

    if (nrow(small_abund) > 0) {
      small_abund <- small_abund %>%
        group_by(Group) %>%
        summarise(Taxa = "Others", value = sum(value), .groups = "drop")
      rel_abund <- rbind(rel_abund, small_abund)
    }
  }

  list(rel_abund = rel_abund, rel_abund_orig = rel_abund_orig)
}

#' Create stacked bar plot of relative abundances
#' @param asv_file Path to ASV table
#' @param taxonomy_file Path to taxonomy file
#' @param metadata_file Path to metadata file
#' @param taxonomy_level Taxonomic level
#' @param class_variable Grouping variable
#' @param output_file Output plot file
#' @param hide_small Whether to group small abundances
#' @param rel_abund_file Optional output file for abundance table
create_stacked_abundance_plot <- function(asv_file, taxonomy_file, metadata_file,
                                          taxonomy_level, class_variable,
                                          output_file, hide_small = FALSE,
                                          rel_abund_file = NULL) {
  load_packages(c("ggplot2", "RColorBrewer"))

  # Compute relative abundances
  rel_abunds <- compute_relative_abundance(
    asv_file, taxonomy_file, metadata_file,
    taxonomy_level, class_variable, hide_small
  )

  rel_abund <- rel_abunds$rel_abund
  rel_abund_orig <- rel_abunds$rel_abund_orig

  # Save abundance table if requested
  if (!is.null(rel_abund_file)) {
    save_results_table(rel_abund_orig, rel_abund_file)
  }

  # Create color palette
  n_colors <- length(unique(rel_abund$Taxa))
  palette_fun <- colorRampPalette(brewer.pal(min(12, n_colors), "Paired"))
  my_colors <- palette_fun(n_colors)

  # Create plot
  p <- ggplot(rel_abund, aes(x = Group, y = value, fill = Taxa)) +
    geom_bar(stat = "identity", width = 0.6) +
    theme_minimal() +
    theme(
      axis.text.x = element_text(angle = 45, hjust = 1),
      legend.position = "bottom"
    ) +
    labs(
      y = "Relative Abundance (%)",
      x = "",
      fill = "Taxa"
    ) +
    scale_fill_manual(values = my_colors)

  # Save plot
  save_plot(p, output_file)
}

#' Create pie chart of relative abundances
#' @param asv_file Path to ASV table
#' @param taxonomy_file Path to taxonomy file
#' @param metadata_file Path to metadata file
#' @param taxonomy_level Taxonomic level
#' @param class_variable Grouping variable
#' @param output_file Output plot file
#' @param rel_abund_file Optional output file for abundance table
create_abundance_pie_plot <- function(asv_file, taxonomy_file, metadata_file,
                                      taxonomy_level, class_variable,
                                      output_file, rel_abund_file = NULL) {
  load_packages(c("ggpubr", "RColorBrewer"))

  # Compute relative abundances (with small groups hidden)
  rel_abunds <- compute_relative_abundance(
    asv_file, taxonomy_file, metadata_file,
    taxonomy_level, class_variable,
    hide_small = TRUE
  )

  rel_abund <- rel_abunds$rel_abund
  rel_abund_orig <- rel_abunds$rel_abund_orig

  # Save abundance table if requested
  if (!is.null(rel_abund_file)) {
    save_results_table(rel_abund_orig, rel_abund_file)
  }

  # Create color palette
  n_colors <- length(unique(rel_abund$Taxa))
  palette_fun <- colorRampPalette(brewer.pal(min(12, n_colors), "Paired"))
  my_colors <- palette_fun(n_colors)

  # Create plot
  p <- ggdonutchart(rel_abund, "value", fill = "Taxa", palette = my_colors) +
    theme_void() +
    facet_wrap(~Group)

  # Save plot
  save_plot(p, output_file)
}

# =============================================================================
# DIFFERENTIAL ABUNDANCE PLOTTING FUNCTIONS
# =============================================================================

#' Create plot of top abundant features
#' @param deseq2_results DESeq2 results object
#' @param n Number of top features to show
#' @param class_variable Grouping variable
#' @param group1 First group
#' @param group2 Second group
#' @param output_file Output plot file
create_top_frequency_plot <- function(deseq2_results, n, class_variable,
                                      group1, group2, output_file) {
  load_packages(c("ggplot2", "gtools", "phyloseq"))

  res <- deseq2_results$all
  phyloseq_data <- deseq2_results$phyloseq_data

  # Calculate frequencies
  otu_table <- as(otu_table(phyloseq_data), "matrix")
  sample_data <- as(sample_data(phyloseq_data), "data.frame")

  g1_samples <- rownames(sample_data)[sample_data[[class_variable]] == group1]
  g2_samples <- rownames(sample_data)[sample_data[[class_variable]] == group2]

  g1_freqs <- rowSums(otu_table[, g1_samples, drop = FALSE]) /
    sum(otu_table[, g1_samples, drop = FALSE])
  g2_freqs <- rowSums(otu_table[, g2_samples, drop = FALSE]) /
    sum(otu_table[, g2_samples, drop = FALSE])

  # Prepare data
  freq_data <- data.frame(
    OTU.ID = rownames(otu_table),
    g1_freqs = g1_freqs,
    g2_freqs = g2_freqs,
    Sum = g1_freqs + g2_freqs
  )

  res$OTU.NAME <- res$OTU.ID
  res$OTU.ID <- rownames(res)

  # Merge and filter
  combined_data <- merge(freq_data, res, by = "OTU.ID", all.x = TRUE)
  combined_data <- combined_data[combined_data$Sum != 0, ]
  combined_data <- combined_data[order(combined_data$Sum, decreasing = TRUE), ]

  n <- min(n, nrow(combined_data))
  combined_data <- combined_data[seq_len(n), ]
  combined_data$pvalue_sign <- gtools::stars.pval(combined_data$pvalue)

  # Create plot
  p <- ggplot(
    combined_data,
    aes(x = log2FoldChange, y = reorder(OTU.NAME, log2FoldChange))
  ) +
    geom_col(aes(fill = Sum), color = "black") +
    geom_text(aes(label = pvalue_sign), size = 3) +
    theme_minimal() +
    labs(
      x = "Log2 Fold Change",
      y = "",
      fill = "Total Frequency"
    ) +
    theme(legend.position = "bottom")

  save_plot(p, output_file)
}

#' Create plot of most significant features
#' @param deseq2_results DESeq2 results object
#' @param n Number of top features to show
#' @param class_variable Grouping variable
#' @param group1 First group
#' @param group2 Second group
#' @param output_file Output plot file
create_top_significance_plot <- function(deseq2_results, n, class_variable,
                                         group1, group2, output_file) {
  load_packages(c("ggplot2", "gtools"))

  res <- deseq2_results$all
  res$OTU.NAME <- res$OTU.ID
  res$OTU.ID <- rownames(res)

  # Sort by p-value and take top n
  res <- res[order(res$pvalue, decreasing = FALSE), ]
  n <- min(n, nrow(res))
  res <- res[seq_len(n), ]
  res$pvalue_sign <- gtools::stars.pval(res$pvalue)

  # Create plot
  p <- ggplot(res, aes(x = log2FoldChange, y = reorder(OTU.NAME, log2FoldChange))) +
    geom_col(aes(fill = pvalue), color = "black") +
    geom_text(aes(label = pvalue_sign), size = 3) +
    theme_minimal() +
    labs(
      x = "Log2 Fold Change",
      y = "",
      fill = "P-value"
    ) +
    theme(legend.position = "bottom")

  save_plot(p, output_file)
}

#' Create plot of features with highest fold changes
#' @param deseq2_results DESeq2 results object
#' @param n Number of top features to show
#' @param class_variable Grouping variable
#' @param group1 First group
#' @param group2 Second group
#' @param output_file Output plot file
create_top_foldchange_plot <- function(deseq2_results, n, class_variable,
                                       group1, group2, output_file) {
  load_packages(c("ggplot2", "gtools"))

  res <- deseq2_results$all
  res$OTU.NAME <- res$OTU.ID
  res$OTU.ID <- rownames(res)

  # Filter non-zero fold changes and sort by absolute value
  res <- res[res$log2FoldChange != 0, ]
  res <- res[order(abs(res$log2FoldChange), decreasing = TRUE), ]

  n <- min(n, nrow(res))
  res <- res[seq_len(n), ]
  res$pvalue_sign <- gtools::stars.pval(res$pvalue)

  # Create plot
  p <- ggplot(res, aes(x = log2FoldChange, y = reorder(OTU.NAME, log2FoldChange))) +
    geom_col(aes(fill = pvalue), color = "black") +
    geom_text(aes(label = pvalue_sign), size = 3) +
    theme_minimal() +
    labs(
      x = "Log2 Fold Change",
      y = "",
      fill = "P-value"
    ) +
    theme(legend.position = "bottom")

  save_plot(p, output_file)
}

# =============================================================================
# CORRELATION ANALYSIS FUNCTIONS
# =============================================================================

#' Prepare correlation network from OTU table
#' @param otus OTU table as a matrix
#' @param taxas Taxonomy table as a matrix
prepare_correlation_network <- function(otus, taxas) {
  corrs <- cor(t(otus), method = "spearman")
  corrs[is.na(corrs)] <- 0  # Replace NA with 0
  indexes <- which(abs(corrs) > 0, arr.ind = TRUE)
  indexes <- indexes[indexes[, 1] != indexes[, 2], ]  # Remove self-correlations
  indexes <- indexes[indexes[, 1] < indexes[, 2], ] # Keep only one direction of correlation
  edges <- data.frame(
    id1    = indexes[, 1],
    id2    = indexes[, 2],
    source = rownames(corrs)[indexes[, 1]],
    target = rownames(corrs)[indexes[, 2]],
    correlation = corrs[indexes],
    taxa_source = unname(taxas[rownames(corrs)[indexes[, 1]], 1]),
    taxa_target = unname(taxas[rownames(corrs)[indexes[, 2]], 1]),
    stringsAsFactors = FALSE
  )
  rownames(edges) <- paste0(edges$source, "_", edges$target)
  edges
}

#' Compute interaction network between two groups
#' @param asv_file Path to ASV table
#' @param taxonomy_file Path to taxonomy file
#' @param metadata_file Path to metadata file
#' @param taxonomy_level Taxonomic level
#' @param class_variable Grouping variable
#' @param group1 First group for comparison
#' @param group2 Second group for comparison
#' @param corr_threshold Correlation threshold for filtering
#' @param output_file Output file for interaction network
compute_interaction_network <- function(asv_file, taxonomy_file, metadata_file, 
                                        taxonomy_level, class_variable, group1, 
                                        group2, corr_threshold, output_file) {
  load_packages(c("dplyr", "tidyr"))
  data <- prepare_deseq_data(asv_file, taxonomy_file, metadata_file, 
                             taxonomy_level, class_variable, group1, group2)
  otus    <- as(otu_table(data), "matrix")
  samples <- as(sample_data(data), "data.frame")
  taxas   <- as(tax_table(data), "matrix")
  
  group1_samples <- rownames(samples)[samples[[class_variable]] == group1]
  group2_samples <- rownames(samples)[samples[[class_variable]] == group2]
  otus_group1 <- otus[, group1_samples]
  otus_group2 <- otus[, group2_samples]
  network_group1_all <- prepare_correlation_network(otus_group1, taxas)
  network_group2_all <- prepare_correlation_network(otus_group2, taxas)
  # Filter networks by correlation threshold
  network_group1 <- network_group1_all[abs(network_group1_all$correlation) > corr_threshold, ]
  network_group2 <- network_group2_all[abs(network_group2_all$correlation) > corr_threshold, ]
  # Compare networks
  diff_network_group1 <- setdiff(rownames(network_group1), rownames(network_group2))
  diff_network_group2 <- setdiff(rownames(network_group2), rownames(network_group1))
  # Diffeential networks
  network_group1 <- network_group1[diff_network_group1,]
  network_group2 <- network_group2[diff_network_group2,]
  # Compute interaction p-values
  grid_otus <- data.frame(
    source = c(network_group1$id1, network_group2$id1),
    target = c(network_group1$id2, network_group2$id2)
  )
  m <- factor(samples[[class_variable]], levels = c(group1, group2))
  p_interaction <- sapply(1:nrow(grid_otus), function (i) {
    tryCatch({
      # Get the OTUs for the current pair
      o1 <- otus[grid_otus$source[i],]
      o2 <- otus[grid_otus$target[i],]
      # gene_B ~ gene_A * subgroup
      lmfit <- stats::lm(o2 ~ o1 * m)
      stats::coef(summary(lmfit))[4,4]
    }, error= function(e) {
      1
    })
  })
  fdr_interaction <- p.adjust(p_interaction, method = "fdr")
  # Create interaction network
  interaction_network <- data.frame(
    source = rownames(otus)[grid_otus$source],
    target = rownames(otus)[grid_otus$target],
    p_value = p_interaction,
    fdr = fdr_interaction,
    taxa_source = unname(taxas[rownames(otus)[grid_otus$source], 1]),
    taxa_target = unname(taxas[rownames(otus)[grid_otus$target], 1]),
    stringsAsFactors = FALSE
  ) %>%
    left_join(network_group1_all, 
              by = c("source", "target", "taxa_source", "taxa_target")) %>%
    left_join(network_group2_all, 
              by = c("source", "target", "taxa_source", "taxa_target"), 
              suffix = c("_group1", "_group2")) %>%
    select(
      taxa_source, taxa_target, correlation_group1, correlation_group2, 
      p_value, fdr
    )
  interaction_network[is.na(interaction_network)] <- 0
  interaction_network <- interaction_network[order(interaction_network$fdr), ]
  # Save the interaction network to a file
  save_results_table(interaction_network, output_file)
}

# =============================================================================
# MAIN SCRIPT EXECUTION
# =============================================================================

# Parse command line arguments
if (!interactive()) {
  load_packages("optparse")

  option_list <- list(
    make_option("--method",
      type = "character",
      help = "Analysis method to run"
    ),
    make_option("--alpha_diversity_file",
      type = "character",
      help = "Alpha diversity file"
    ),
    make_option("--beta_diversity_file",
      type = "character",
      help = "Beta diversity file"
    ),
    make_option("--metadata_file",
      type = "character",
      help = "Metadata file"
    ),
    make_option("--class_variable",
      type = "character",
      help = "Class variable for grouping"
    ),
    make_option("--color_var",
      type = "character",
      help = "Color variable for beta diversity"
    ),
    make_option("--comparisons",
      type = "character",
      help = "Comparisons for alpha diversity (format: 'A,B;C,D')"
    ),
    make_option("--output_file",
      type = "character",
      help = "Output file path"
    ),
    make_option("--asv_file",
      type = "character",
      help = "ASV table file"
    ),
    make_option("--taxonomy_file",
      type = "character",
      help = "Taxonomy file"
    ),
    make_option("--taxonomy_level",
      type = "integer",
      help = "Taxonomy level (1-7)"
    ),
    make_option("--group1",
      type = "character",
      help = "First group for comparison"
    ),
    make_option("--group2",
      type = "character",
      help = "Second group for comparison"
    ),
    make_option("--pv_threshold",
      type = "double",
      help = "P-value threshold"
    ),
    make_option("--fdr_threshold",
      type = "double",
      help = "FDR threshold"
    ),
    make_option("--output_prefix",
      type = "character",
      help = "Output prefix for differential abundance"
    ),
    make_option("--deseq2_results_file",
      type = "character",
      help = "RDS file from DESeq2 analysis"
    ),
    make_option("--n",
      type = "integer",
      help = "Number of top features to plot"
    ),
    make_option("--hide_small",
      type = "logical",
      default = FALSE,
      help = "Hide small abundances (default: FALSE)"
    ),
    make_option("--rel_abund_file",
      type = "character",
      help = "Output file for relative abundance table"
    ),
    make_option("--corr_threshold",
      type = "double",
      default = 0.6,
      help = "Correlation threshold for interaction network"
    )
  )

  parser <- OptionParser(option_list = option_list)
  args <- parse_args(parser)

  # Execute based on method
  method <- args$method
  if (is.null(method)) {
    stop("--method is required. Use --help for available options.")
  }
  tryCatch(
    {
      switch(method,
        "alpha_diversity" = {
          required_args <- c(
            "alpha_diversity_file", "metadata_file",
            "class_variable", "output_file"
          )
          if (any(sapply(required_args, function(x) is.null(args[[x]])))) {
            stop("Missing required arguments for alpha_diversity method")
          }

          comparisons <- NULL
          if (!is.null(args$comparisons) && nchar(args$comparisons) > 0) {
            comparisons <- strsplit(args$comparisons, ";")[[1]]
            comparisons <- lapply(
              comparisons,
              function(x) unlist(strsplit(x, ","))
            )
          }

          alpha_diversity_plot(
            diversity_file = args$alpha_diversity_file,
            metadata_file = args$metadata_file,
            class_variable = args$class_variable,
            comparisons = comparisons,
            output_file = args$output_file
          )
        },
        "beta_diversity" = {
          required_args <- c(
            "beta_diversity_file", "metadata_file",
            "color_var", "output_file"
          )
          if (any(sapply(required_args, function(x) is.null(args[[x]])))) {
            stop("Missing required arguments for beta_diversity method")
          }

          beta_diversity_plot(
            diversity_file = args$beta_diversity_file,
            metadata_file = args$metadata_file,
            color_var = args$color_var,
            output_file = args$output_file
          )
        },
        "differential_abundance" = {
          required_args <- c(
            "asv_file", "taxonomy_file", "metadata_file",
            "taxonomy_level", "class_variable", "group1",
            "group2", "pv_threshold", "fdr_threshold",
            "output_prefix"
          )
          if (any(sapply(required_args, function(x) is.null(args[[x]])))) {
            stop("Missing required arguments for differential_abundance method")
          }

          run_deseq2_analysis(
            asv_file = args$asv_file,
            taxonomy_file = args$taxonomy_file,
            metadata_file = args$metadata_file,
            taxonomy_level = args$taxonomy_level,
            class_variable = args$class_variable,
            group1 = args$group1,
            group2 = args$group2,
            pv_threshold = args$pv_threshold,
            fdr_threshold = args$fdr_threshold,
            output_prefix = args$output_prefix
          )
        },
        "picrust_functional" = {
          required_args <- c(
            "asv_file", "metadata_file", "class_variable",
            "group1", "group2", "pv_threshold", "fdr_threshold",
            "output_prefix"
          )
          if (any(sapply(required_args, function(x) is.null(args[[x]])))) {
            stop("Missing required arguments for picrust_functional method")
          }

          run_picrust_analysis(
            asv_file = args$asv_file,
            metadata_file = args$metadata_file,
            class_variable = args$class_variable,
            group1 = args$group1,
            group2 = args$group2,
            pv_threshold = args$pv_threshold,
            fdr_threshold = args$fdr_threshold,
            output_prefix = args$output_prefix
          )
        },
        "top_freq_plot" = {
          required_args <- c(
            "deseq2_results_file", "n", "class_variable",
            "group1", "group2", "output_file"
          )
          if (any(sapply(required_args, function(x) is.null(args[[x]])))) {
            stop("Missing required arguments for top_freq_plot method")
          }

          validate_file(args$deseq2_results_file, "DESeq2 results file")
          deseq2_results <- readRDS(args$deseq2_results_file)

          create_top_frequency_plot(
            deseq2_results = deseq2_results,
            n = args$n,
            class_variable = args$class_variable,
            group1 = args$group1,
            group2 = args$group2,
            output_file = args$output_file
          )
        },
        "top_sign_plot" = {
          required_args <- c(
            "deseq2_results_file", "n", "class_variable",
            "group1", "group2", "output_file"
          )
          if (any(sapply(required_args, function(x) is.null(args[[x]])))) {
            stop("Missing required arguments for top_sign_plot method")
          }

          validate_file(args$deseq2_results_file, "DESeq2 results file")
          deseq2_results <- readRDS(args$deseq2_results_file)

          create_top_significance_plot(
            deseq2_results = deseq2_results,
            n = args$n,
            class_variable = args$class_variable,
            group1 = args$group1,
            group2 = args$group2,
            output_file = args$output_file
          )
        },
        "top_fc_plot" = {
          required_args <- c(
            "deseq2_results_file", "n", "class_variable",
            "group1", "group2", "output_file"
          )
          if (any(sapply(required_args, function(x) is.null(args[[x]])))) {
            stop("Missing required arguments for top_fc_plot method")
          }

          validate_file(args$deseq2_results_file, "DESeq2 results file")
          deseq2_results <- readRDS(args$deseq2_results_file)

          create_top_foldchange_plot(
            deseq2_results = deseq2_results,
            n = args$n,
            class_variable = args$class_variable,
            group1 = args$group1,
            group2 = args$group2,
            output_file = args$output_file
          )
        },
        "stacked_abundance_barplot" = {
          required_args <- c(
            "asv_file", "taxonomy_file", "metadata_file",
            "taxonomy_level", "class_variable", "output_file"
          )
          if (any(sapply(required_args, function(x) is.null(args[[x]])))) {
            stop("Missing required arguments for stacked_abundance_barplot method")
          }

          create_stacked_abundance_plot(
            asv_file = args$asv_file,
            taxonomy_file = args$taxonomy_file,
            metadata_file = args$metadata_file,
            taxonomy_level = args$taxonomy_level,
            class_variable = args$class_variable,
            output_file = args$output_file,
            hide_small = ifelse(is.null(args$hide_small), FALSE, args$hide_small),
            rel_abund_file = args$rel_abund_file
          )
        },
        "abundance_pie_plot" = {
          required_args <- c(
            "asv_file", "taxonomy_file", "metadata_file",
            "taxonomy_level", "class_variable", "output_file"
          )
          if (any(sapply(required_args, function(x) is.null(args[[x]])))) {
            stop("Missing required arguments for abundance_pie_plot method")
          }

          create_abundance_pie_plot(
            asv_file = args$asv_file,
            taxonomy_file = args$taxonomy_file,
            metadata_file = args$metadata_file,
            taxonomy_level = args$taxonomy_level,
            class_variable = args$class_variable,
            output_file = args$output_file,
            rel_abund_file = args$rel_abund_file
          )
        },
        "correlation_network" = {
          required_args <- c(
            "asv_file", "taxonomy_file", "metadata_file",
            "taxonomy_level", "class_variable", "group1",
            "group2", "corr_threshold", "output_file"
          )
          if (any(sapply(required_args, function(x) is.null(args[[x]])))) {
            stop("Missing required arguments for correlation_network method")
          }

          compute_interaction_network(
            asv_file = args$asv_file,
            taxonomy_file = args$taxonomy_file,
            metadata_file = args$metadata_file,
            taxonomy_level = args$taxonomy_level,
            class_variable = args$class_variable,
            group1 = args$group1,
            group2 = args$group2,
            corr_threshold = args$corr_threshold,
            output_file = args$output_file
          )
        },
        {
          stop(paste("Unknown method:", method))
        }
      )
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
