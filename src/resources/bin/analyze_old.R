suppressWarnings(suppressPackageStartupMessages(library(qiime2R)))

# Alpha Diversity Plot
alpha_diversity <- function(diversity_file,
                            metadata_file,
                            class_variable,
                            comparisons,
                            output_file) {
  suppressWarnings(suppressPackageStartupMessages(library(ggpubr)))
  suppressWarnings(suppressPackageStartupMessages(library(dplyr)))
  suppressWarnings(suppressPackageStartupMessages(library(tibble)))
  suppressWarnings(suppressPackageStartupMessages(library(tidyr)))
  suppressWarnings(suppressPackageStartupMessages(library(ggplot2)))
  if (!is.null(comparisons)) {
    if (!is.list(comparisons)) {
      stop("Comparisons must be a list")
    }
    if (any(sapply(comparisons, length) > 2)) {
      stop("Each comparison must have exactly 2 groups")
    }
  }
  diversity <- read_qza(diversity_file)
  metadata <- read.table(metadata_file, sep = "\t", header = TRUE)
  colnames(metadata)[1] <- "sample_id"
  metadata$sample_id <- trimws(metadata$sample_id)
  diversity <- diversity$data
  colnames(diversity)[1] <- "alpha_diversity"
  diversity <- diversity %>% rownames_to_column("sample_id")
  diversity$sample_id <- trimws(diversity$sample_id)
  metadata <- metadata %>%
    left_join(diversity, by = "sample_id") %>%
    na.omit()
  if (!class_variable %in% colnames(metadata)) {
    stop(paste("Variable", class_variable, "not found in metadata"))
  }

  p <- ggboxplot(
    metadata,
    x = class_variable,
    y = "alpha_diversity",
    color = class_variable,
    legend = "none",
    ylab = "Alpha Diversity",
    xlab = class_variable,
    add = "jitter"
  ) + theme_minimal()
  if (!is.null(comparisons) && !all(is.na(max(metadata$alpha_diversity)))) {
    p <- p +
      stat_compare_means(
        comparisons = comparisons,
        method = "t.test"
      ) +
      stat_compare_means(
        label.y = max(metadata$alpha_diversity) * 1.20,
        method = "anova"
      )
  }

  ggsave(
    output_file,
    plot = p,
    bg = "transparent",
    width = 15,
    height = 11,
    dpi = 300,
    units = "in",
    device = "svg"
  )
}


#------------------------------------------------------------------------------#
# Beta Diversity Plot

beta_diversity <- function(diversity_file,
                           metadata_file,
                           color_var,
                           output_file) {
  suppressWarnings(suppressPackageStartupMessages(library(ggplot2)))
  suppressWarnings(suppressPackageStartupMessages(library(dplyr)))
  suppressWarnings(suppressPackageStartupMessages(library(tibble)))
  suppressWarnings(suppressPackageStartupMessages(library(tidyr)))
  suppressWarnings(suppressPackageStartupMessages(library(ggforce)))
  suppressWarnings(suppressPackageStartupMessages(library(phyloseq)))
  metadata <- read.table(metadata_file, sep = "\t", header = TRUE)
  colnames(metadata)[1] <- "SampleID"
  metadata$SampleID <- trimws(metadata$SampleID)
  if (!color_var %in% colnames(metadata)) {
    stop(paste("Variable", x_var, "not found in metadata"))
  }
  discrete <- FALSE
  if (class(metadata[[color_var]]) == "character") {
    metadata[[color_var]] <- factor(metadata[[color_var]])
    discrete <- TRUE
  }
  diversity <- read_qza(diversity_file)
  pc1_explain <- round(100 * diversity$data$ProportionExplained[1], 2)
  pc2_explain <- round(100 * diversity$data$ProportionExplained[2], 2)
  p <- diversity$data$Vectors %>%
    select(SampleID, PC1, PC2) %>%
    left_join(metadata) %>%
    na.omit() %>%
    ggplot(aes(x = PC1, y = PC2)) +
    geom_point(alpha = 0.5, size = 5, aes(colour = .data[[color_var]])) +
    xlab(paste("PC1: ", pc1_explain, "%")) +
    ylab(paste("PC2: ", pc2_explain, "%")) +
    theme_minimal() +
    stat_ellipse(aes(colour = .data[[color_var]]), level = 0.95)

  ggsave(
    output_file,
    plot = p,
    bg = "transparent",
    width = 15,
    height = 10,
    dpi = 300,
    units = "in",
    device = "svg"
  )
}

#------------------------------------------------------------------------------#
# Differential Abundance Analysis

convert_asv_to_otu_table <- function(asv_file, taxonomy_file, taxonomy_level) {
  suppressWarnings(suppressPackageStartupMessages(library(dplyr)))
  suppressWarnings(suppressPackageStartupMessages(library(tibble)))
  if (taxonomy_level < 1 || taxonomy_level > 7) {
    stop("Invalid taxonomy level")
  }
  asv_table <- read.table(
    asv_file,
    sep = "\t",
    header = TRUE,
    stringsAsFactors = FALSE,
    row.names = 1,
    check.names = FALSE
  )
  taxonomy <- read.table(
    taxonomy_file,
    sep = "\t",
    header = TRUE,
    stringsAsFactors = FALSE
  )

  tmp <- lapply(strsplit(taxonomy$Taxon, ";\\s*"), function(x) {
    (strsplit(x, "__"))
  })
  tmp <- lapply(tmp, function(x) {
    x <- setNames(sapply(x, function(x) {
      (x[2])
    }), sapply(x, function(x) {
      (x[1])
    }))
    x <- data.frame(t(x[c("d", "p", "c", "o", "f", "g", "s")]), row.names = NULL, stringsAsFactors = FALSE)
    colnames(x) <- c("d", "p", "c", "o", "f", "g", "s")
    x
  })
  tmp <- do.call(rbind, tmp)
  for (c in ncol(tmp)) {
    tmp[[c]] <- trimws(tmp[[c]])
    tmp[[c]][tmp[[c]] == ""] <- NA
  }
  tmp[is.na(tmp)] <- ""
  tmp <- tmp[, seq_len(taxonomy_level), drop = FALSE]
  taxonomy$TaxonAtLevel <- apply(tmp, 1, function(x) {
    (paste(
      colnames(tmp), x,
      sep = "__", collapse = ";"
    ))
  })
  otu_table <- asv_table %>%
    rownames_to_column("Feature.ID") %>%
    left_join(taxonomy, by = "Feature.ID") %>%
    select(-Feature.ID, -Taxon, -Confidence) %>%
    group_by(TaxonAtLevel) %>%
    summarise(across(everything(), sum)) %>%
    dplyr::relocate(TaxonAtLevel, .before = 1)
  colnames(otu_table)[1] <- "OTU.ID"
  class(otu_table) <- "data.frame"
  otu_table
}

prepare_deseq_data <- function(asv_file,
                               taxonomy_file,
                               metadata_file,
                               taxonomy_level,
                               class_variable,
                               group1,
                               group2) {
  suppressWarnings(suppressPackageStartupMessages(library(phyloseq)))
  otu_data <- convert_asv_to_otu_table(asv_file, taxonomy_file, taxonomy_level)
  otu_data$ID <- paste0("Taxa", seq_len(nrow(otu_data)))
  taxa_leaves <- otu_data[, c(1, ncol(otu_data))]
  rownames(otu_data) <- otu_data$ID
  otu_data$ID <- ordered(otu_data$ID)
  otu_data$OTU.ID <- NULL
  otu_data$ID <- NULL
  # taxonomy
  rownames(taxa_leaves) <- taxa_leaves$ID
  taxa_leaves$ID <- ordered(taxa_leaves$ID)
  taxa_leaves$ID <- NULL
  taxa_leaves <- as.matrix(taxa_leaves)
  taxa <- tax_table(taxa_leaves)
  # metadata
  metadata <- read.table(metadata_file, sep = "\t", header = TRUE)
  colnames(metadata)[1] <- "sample.id"
  metadata$sample.id <- trimws(metadata$sample.id)
  rownames(metadata) <- metadata$sample.id
  metadata$sample.id <- NULL
  map <- sample_data(metadata)
  map <- map[map[, class_variable] == group1 |
    map[, class_variable] == group2]
  pv <- which(colnames(otu_data) %in% rownames(map))
  otu_data <- otu_data[pv]
  pv <- which(rownames(map) %in% colnames(otu_data))
  map <- map[pv]
  otu <- otu_table(otu_data, taxa_are_rows = TRUE)
  data_shotgun <- merge_phyloseq(otu, taxa, map)
  data_shotgun <- prune_samples(sample_sums(data_shotgun) > 1, data_shotgun)
  data_shotgun <- prune_taxa(taxa_sums(data_shotgun) > 1, data_shotgun)
  data_shotgun
}

extract_from_result <- function(results, phylo_data, filter_expr) {
  filtered <- which(filter_expr)
  if (length(filtered) == 0) {
    return(NULL)
  }
  res_table <- results[filtered, ]
  res_table <- cbind(as(res_table, "data.frame"), as(tax_table(phylo_data)[rownames(res_table), ], "matrix"))
  res_table$log2FoldChange[is.na(res_table$log2FoldChange)] <- 0
  res_table$pvalue[is.na(res_table$pvalue)] <- 1
  res_table$padj[is.na(res_table$padj)] <- 1
  res_table <- na.omit(res_table)
  res_table
}

run_deseq2 <- function(asv_file,
                       taxonomy_file,
                       metadata_file,
                       taxonomy_level,
                       class_variable,
                       group1,
                       group2,
                       pv_threshold,
                       fdr_threshold,
                       output_prefix) {
  suppressWarnings(suppressPackageStartupMessages(library(DESeq2)))
  data_shotgun <- prepare_deseq_data(
    asv_file,
    taxonomy_file,
    metadata_file,
    taxonomy_level,
    class_variable,
    group1,
    group2
  )
  diagdds <- phyloseq_to_deseq2(data_shotgun, as.formula(paste("~", class_variable)))
  diagdds <- DESeq(diagdds, test = "Wald", fitType = "parametric")
  results <- results(diagdds, contrast = c(class_variable, group1, group2))

  all_degs_output <- paste0(output_prefix, "_all.tsv")
  pv_filtered_output <- paste0(output_prefix, "_pvalue.tsv")
  fdr_filtered_output <- paste0(output_prefix, "_fdr.tsv")

  res_all <- extract_from_result(results, data_shotgun, results$pvalue <= 1)
  res_pv <- extract_from_result(results, data_shotgun, results$pvalue < pv_threshold)
  res_fdr <- extract_from_result(results, data_shotgun, results$padj < fdr_threshold)

  write.table(
    res_all,
    all_degs_output,
    sep = "\t",
    quote = FALSE,
    row.names = FALSE,
    col.names = TRUE,
    na = "NA"
  )
  if (!is.null(res_pv)) {
    write.table(
      res_pv,
      pv_filtered_output,
      sep = "\t",
      quote = FALSE,
      row.names = FALSE,
      col.names = TRUE,
      na = "NA"
    )
  }
  if (!is.null(res_fdr)) {
    write.table(
      res_fdr,
      fdr_filtered_output,
      sep = "\t",
      quote = FALSE,
      row.names = TRUE,
      col.names = TRUE,
      na = "NA"
    )
  }
  res <- list(
    all = res_all,
    pv_filtered = res_pv,
    fdr_filtered = res_fdr,
    data_shotgun = data_shotgun
  )
  saveRDS(res, paste0(output_prefix, "_raw.rds"))
  res
}

#------------------------------------------------------------------------------#
# Picrust Functional Analysis

prepare_picrust_data <- function(asv_file,
                                 metadata_file,
                                 class_variable,
                                 group1,
                                 group2) {
  suppressWarnings(suppressPackageStartupMessages(library(readr)))
  suppressWarnings(suppressPackageStartupMessages(library(phyloseq)))
  picrust_data <- read_tsv(asv_file, col_names = TRUE)
  class(picrust_data) <- "data.frame"
  colnames(picrust_data)[1] <- "ID"
  colnames(picrust_data)[2] <- "OTU.ID"
  taxa_leaves <- picrust_data[, c(1, 2)]
  rownames(picrust_data) <- picrust_data$ID
  picrust_data$ID <- ordered(picrust_data$ID)
  picrust_data$OTU.ID <- NULL
  picrust_data$ID <- NULL
  # taxonomy
  rownames(taxa_leaves) <- taxa_leaves$ID
  taxa_leaves$ID <- ordered(taxa_leaves$ID)
  taxa_leaves$ID <- NULL
  taxa_leaves <- as.matrix(taxa_leaves)
  taxa <- tax_table(taxa_leaves)
  # metadata
  metadata <- read.table(metadata_file, sep = "\t", header = TRUE)
  colnames(metadata)[1] <- "sample.id"
  metadata$sample.id <- trimws(metadata$sample.id)
  rownames(metadata) <- metadata$sample.id
  metadata$sample.id <- NULL
  map <- sample_data(metadata)
  map <- map[map[, class_variable] == group1 |
    map[, class_variable] == group2]
  pv <- which(colnames(picrust_data) %in% rownames(map))
  picrust_data <- picrust_data[pv]
  pv <- which(rownames(map) %in% colnames(picrust_data))
  map <- map[pv]
  otu <- otu_table(picrust_data, taxa_are_rows = TRUE)
  data_picrust <- merge_phyloseq(otu, taxa, map)
  data_picrust <- prune_samples(sample_sums(data_picrust) > 1, data_picrust)
  data_picrust <- prune_taxa(taxa_sums(data_picrust) > 1, data_picrust)
  data_picrust
}

run_deseq2_picrust <- function(asv_file,
                               metadata_file,
                               class_variable,
                               group1,
                               group2,
                               pv_threshold,
                               fdr_threshold,
                               output_prefix) {
  suppressWarnings(suppressPackageStartupMessages(library(DESeq2)))
  data_shotgun <- prepare_picrust_data(
    asv_file,
    metadata_file,
    class_variable,
    group1,
    group2
  )
  diagdds <- phyloseq_to_deseq2(data_shotgun, as.formula(paste("~", class_variable)))
  diagdds <- DESeq(diagdds, test = "Wald", fitType = "parametric")
  results <- results(diagdds, contrast = c(class_variable, group1, group2))

  all_degs_output <- paste0(output_prefix, "_all.tsv")
  pv_filtered_output <- paste0(output_prefix, "_pvalue.tsv")
  fdr_filtered_output <- paste0(output_prefix, "_fdr.tsv")

  res_all <- extract_from_result(results, data_shotgun, results$pvalue <= 1)
  res_pv <- extract_from_result(results, data_shotgun, results$pvalue < pv_threshold)
  res_fdr <- extract_from_result(results, data_shotgun, results$padj < fdr_threshold)

  write.table(
    res_all,
    all_degs_output,
    sep = "\t",
    quote = FALSE,
    row.names = FALSE,
    col.names = TRUE,
    na = "NA"
  )
  if (!is.null(res_pv)) {
    write.table(
      res_pv,
      pv_filtered_output,
      sep = "\t",
      quote = FALSE,
      row.names = FALSE,
      col.names = TRUE,
      na = "NA"
    )
  }
  if (!is.null(res_fdr)) {
    write.table(
      res_fdr,
      fdr_filtered_output,
      sep = "\t",
      quote = FALSE,
      row.names = TRUE,
      col.names = TRUE,
      na = "NA"
    )
  }
  res <- list(
    all = res_all,
    pv_filtered = res_pv,
    fdr_filtered = res_fdr,
    data_shotgun = data_shotgun
  )
  saveRDS(res, paste0(output_prefix, "_raw.rds"))
  res
}


#------------------------------------------------------------------------------#
# Differential Abundance Analysis Plots

compute_top_freq_plot <- function(deseq2_results,
                                  n,
                                  class_variable,
                                  group1,
                                  group2,
                                  output_file) {
  suppressWarnings(suppressPackageStartupMessages(library(ggplot2)))
  suppressWarnings(suppressPackageStartupMessages(library(gtools)))
  res <- deseq2_results$all
  data <- deseq2_results$data_shotgun
  otu_table <- as(otu_table(data), "matrix")
  sample_data <- as(sample_data(data), "data.frame")
  g1_samples <- rownames(sample_data)[sample_data[[class_variable]] == group1]
  g2_samples <- rownames(sample_data)[sample_data[[class_variable]] == group2]
  g1_otu <- otu_table[, g1_samples]
  g2_otu <- otu_table[, g2_samples]
  g1_sum <- rowSums(g1_otu)
  g2_sum <- rowSums(g2_otu)
  g1_freqs <- g1_sum / sum(g1_sum)
  g2_freqs <- g2_sum / sum(g2_sum)
  freq_data <- data.frame(
    OTU.ID = rownames(otu_table),
    g1_freqs = g1_freqs,
    g2_freqs = g2_freqs
  )
  freq_data$Sum <- rowSums(freq_data[, 2:3])
  res$OTU.NAME <- res$OTU.ID
  res$OTU.ID <- rownames(res)
  combined_data <- merge(freq_data, res, all.x = TRUE)
  combined_data <- combined_data[combined_data$Sum != 0, ]
  combined_data <- combined_data[order(combined_data$Sum, decreasing = TRUE), ]
  n <- min(n, nrow(combined_data))
  combined_data <- combined_data[seq_len(n), ]
  combined_data$pvalue_sign <- gtools::stars.pval(combined_data$pvalue)
  combined_data <- combined_data[order(combined_data$log2FoldChange, decreasing = TRUE), ]

  p <- ggplot(combined_data, aes(x = log2FoldChange, y = reorder(OTU.NAME, log2FoldChange))) +
    geom_bar(
      aes(fill = Sum),
      position = "dodge",
      stat = "identity",
      color = "black"
    ) +
    geom_label(aes(label = pvalue_sign), size = 5) +
    theme_minimal() +
    theme(legend.position = "bottom") +
    xlab("LogFC") +
    ylab("") +
    guides(fill = guide_legend("Total Frequency"))
  ggsave(
    output_file,
    plot = p,
    bg = "transparent",
    width = 15,
    height = 10,
    dpi = 300,
    units = "in",
    device = "svg"
  )
}

compute_top_sign_plot <- function(deseq2_results,
                                  n,
                                  class_variable,
                                  group1,
                                  group2,
                                  output_file) {
  suppressWarnings(suppressPackageStartupMessages(library(ggplot2)))
  suppressWarnings(suppressPackageStartupMessages(library(gtools)))
  res <- deseq2_results$all
  res$OTU.NAME <- res$OTU.ID
  res$OTU.ID <- rownames(res)
  n <- min(n, nrow(res))
  res <- res[order(res$pvalue, decreasing = FALSE), ]
  res <- res[seq_len(n), ]
  res$pvalue_sign <- gtools::stars.pval(res$pvalue)
  p <- ggplot(res, aes(x = log2FoldChange, y = reorder(OTU.NAME, log2FoldChange))) +
    geom_bar(
      aes(fill = pvalue),
      position = "dodge",
      stat = "identity",
      color = "black"
    ) +
    geom_label(aes(label = pvalue_sign), size = 5) +
    theme_minimal() +
    theme(legend.position = "bottom") +
    xlab("LogFC") +
    ylab("") +
    guides(fill = guide_legend("p"))
  ggsave(
    output_file,
    plot = p,
    bg = "transparent",
    width = 15,
    height = 10,
    dpi = 300,
    units = "in",
    device = "svg"
  )
}

compute_top_fc_plot <- function(deseq2_results,
                                n,
                                class_variable,
                                group1,
                                group2,
                                output_file) {
  suppressWarnings(suppressPackageStartupMessages(library(ggplot2)))
  suppressWarnings(suppressPackageStartupMessages(library(gtools)))
  res <- deseq2_results$all
  res$OTU.NAME <- res$OTU.ID
  res$OTU.ID <- rownames(res)
  res <- res[res$log2FoldChange != 0, ]
  res <- res[order(abs(res$log2FoldChange), decreasing = FALSE), ]
  n <- min(n, nrow(res))
  res <- res[seq_len(n), ]
  res$pvalue_sign <- gtools::stars.pval(res$pvalue)
  p <- ggplot(res, aes(x = log2FoldChange, y = reorder(OTU.NAME, log2FoldChange))) +
    geom_bar(
      aes(fill = pvalue),
      position = "dodge",
      stat = "identity",
      color = "black"
    ) +
    geom_text(aes(label = pvalue_sign), size = 5) +
    theme_minimal() +
    theme(legend.position = "bottom") +
    xlab("LogFC") +
    ylab("") +
    guides(fill = guide_legend("p"))
  ggsave(
    output_file,
    plot = p,
    bg = "transparent",
    width = 15,
    height = 10,
    dpi = 300,
    units = "in",
    device = "svg"
  )
}

#------------------------------------------------------------------------------#
# Relative Abundance stacked barplot

compute_relative_abundance <- function(asv_file,
                                       taxonomy_file,
                                       metadata_file,
                                       taxonomy_level,
                                       class_variable,
                                       hide_small = FALSE) {
  suppressWarnings(suppressPackageStartupMessages(library(dplyr)))
  suppressWarnings(suppressPackageStartupMessages(library(tidyr)))
  suppressWarnings(suppressPackageStartupMessages(library(RColorBrewer)))
  otu_table <- convert_asv_to_otu_table(asv_file, taxonomy_file, taxonomy_level)
  taxa <- otu_table$OTU.ID
  otu_table$OTU.ID <- NULL
  abund_df <- as.data.frame(t(otu_table))
  colnames(abund_df) <- taxa

  metadata <- read.table(metadata_file, sep = "\t", header = TRUE)
  colnames(metadata)[1] <- "sample.id"
  metadata$sample.id <- trimws(metadata$sample.id)
  rownames(metadata) <- metadata$sample.id

  samples <- intersect(metadata$sample.id, rownames(abund_df))
  abund_df <- abund_df[samples, ]
  metadata <- metadata[samples, ]
  abund_df$Group <- metadata[[class_variable]]
  abund_df <- na.omit(abund_df)

  rel_abund <- abund_df %>%
    group_by(Group) %>%
    summarise(across(everything(), sum))
  rel_abund[, -1] <- round(rel_abund[, -1] / rowSums(rel_abund[, -1]) * 100, digits = 2)
  rel_abund <- rel_abund %>%
    pivot_longer(
      cols = -Group,
      names_to = "Taxa",
      values_to = "value"
    )
  rel_abund$Group <- as.character(rel_abund$Group)
  rel_abund_orig <- rel_abund
  if (hide_small) {
    small_abund <- rel_abund[rel_abund$value <= 1, ]
    rel_abund <- rel_abund[rel_abund$value > 1, ]
    small_abund <- small_abund %>%
      group_by(Group) %>%
      summarise(Taxa = "Others", value = sum(value))
    rel_abund <- rbind(rel_abund, small_abund)
  }
  list(rel_abund, rel_abund_orig)
}

compute_stacked_abundance_barplot <- function(asv_file,
                                              taxonomy_file,
                                              metadata_file,
                                              taxonomy_level,
                                              class_variable,
                                              output_file,
                                              hide_small = FALSE,
                                              rel_abund_file = NULL) {
  suppressWarnings(suppressPackageStartupMessages(library(ggplot2)))
  suppressWarnings(suppressPackageStartupMessages(library(RColorBrewer)))
  suppressWarnings(suppressPackageStartupMessages(library(ggstatsplot)))
  rel_abunds <- compute_relative_abundance(
    asv_file,
    taxonomy_file,
    metadata_file,
    taxonomy_level,
    class_variable,
    hide_small
  )
  rel_abund <- rel_abunds[[1]]
  rel_abund_orig <- rel_abunds[[2]]

  if (!is.null(rel_abund_file)) {
    write.table(
      rel_abund_orig,
      file = rel_abund_file,
      sep = "\t",
      quote = FALSE,
      row.names = FALSE,
      col.names = TRUE
    )
  }

  n_colors <- length(unique(rel_abund$Taxa))
  palette_fun <- colorRampPalette(brewer.pal(12, "Paired"))
  my_colors <- palette_fun(n_colors)

  p <- ggplot(data = rel_abund, aes(x = Group, y = value, fill = Taxa)) +
    geom_bar(stat = "identity", width = 0.6) +
    theme_ggstatsplot() +
    theme(axis.text.x = element_text(angle = 90, hjust = 1), ) +
    ylab("Relative abundance (%)") +
    xlab("") +
    scale_fill_manual(values = my_colors)

  ggsave(
    output_file,
    plot = p,
    bg = "transparent",
    width = 15,
    height = 10,
    dpi = 300,
    units = "in",
    device = "svg"
  )
}

#-----------------------------------------------------------------------------#
# Relative Abundance pie plot

compute_abundance_pie_plot <- function(asv_file,
                                       taxonomy_file,
                                       metadata_file,
                                       taxonomy_level,
                                       class_variable,
                                       output_file,
                                       rel_abund_file = NULL) {
  suppressWarnings(suppressPackageStartupMessages(library(ggpubr)))
  suppressWarnings(suppressPackageStartupMessages(library(RColorBrewer)))
  rel_abunds <- compute_relative_abundance(
    asv_file,
    taxonomy_file,
    metadata_file,
    taxonomy_level,
    class_variable,
    hide_small = TRUE
  )
  rel_abund <- rel_abunds[[1]]
  rel_abund_orig <- rel_abunds[[2]]

  if (!is.null(rel_abund_file)) {
    write.table(
      rel_abund_orig,
      file = rel_abund_file,
      sep = "\t",
      quote = FALSE,
      row.names = FALSE,
      col.names = TRUE
    )
  }
  n_colors <- length(unique(rel_abund$Taxa))
  palette_fun <- colorRampPalette(brewer.pal(12, "Paired"))
  my_colors <- palette_fun(n_colors)

  p <- ggdonutchart(rel_abund, "value", fill = "Taxa", palette = my_colors) +
    theme_void() +
    facet_wrap(~Group)

  ggsave(
    output_file,
    plot = p,
    bg = "transparent",
    width = 15,
    height = 10,
    dpi = 300,
    units = "in",
    device = "svg"
  )
}

suppressWarnings(suppressPackageStartupMessages(library(optparse)))

option_list <- list(
  make_option(c("--method"), type = "character", help = "Method to run: alpha_diversity, beta_diversity, differential_abundance, top_freq_plot, top_sign_plot, top_fc_plot, stacked_abundance_barplot, abundance_pie_plot, picrust_functional"),
  # alpha_diversity
  make_option(
    c("--alpha_diversity_file"),
    type = "character",
    help = "Alpha diversity file"
  ),
  make_option(c("--metadata_file"), type = "character", help = "Metadata file"),
  make_option(c("--class_variable"), type = "character", help = "Class variable for grouping"),
  make_option(c("--comparisons"), type = "character", help = "Comparisons for alpha_diversity, e.g. 'A,B;C,D'"),
  make_option(c("--output_file"), type = "character", help = "Output file"),
  # beta_diversity
  make_option(c("--beta_diversity_file"), type = "character", help = "Beta diversity file"),
  make_option(c("--color_var"), type = "character", help = "Color variable for beta_diversity"),
  # differential_abundance
  make_option(c("--asv_file"), type = "character", help = "ASV file"),
  make_option(c("--taxonomy_file"), type = "character", help = "Taxonomy file"),
  make_option(c("--taxonomy_level"), type = "integer", help = "Taxonomy level (1-7)"),
  make_option(c("--group1"), type = "character", help = "Group 1"),
  make_option(c("--group2"), type = "character", help = "Group 2"),
  make_option(c("--pv_threshold"), type = "double", help = "P-value threshold"),
  make_option(c("--fdr_threshold"), type = "double", help = "FDR threshold"),
  make_option(c("--output_prefix"), type = "character", help = "Output prefix for differential_abundance"),
  # top plots
  make_option(c("--deseq2_results_file"), type = "character", help = "RDS file from run_deseq2"),
  make_option(c("--n"), type = "integer", help = "Number of top features"),
  # stacked_abundance_barplot
  make_option(
    c("--hide_small"),
    type = "logical",
    default = FALSE,
    help = "Hide small abundances (default: FALSE)"
  ),
  make_option(c("--rel_abund_file"), type = "character", help = "Output file for relative abundance table")
)

parser <- OptionParser(option_list = option_list)
args <- parse_args(parser)

method <- args$method
if (is.null(method)) {
  stop("--method is required. Use --help for options.")
}

if (method == "alpha_diversity") {
  suppressWarnings(suppressPackageStartupMessages(library(ggpubr)))
  suppressWarnings(suppressPackageStartupMessages(library(dplyr)))
  suppressWarnings(suppressPackageStartupMessages(library(tibble)))
  suppressWarnings(suppressPackageStartupMessages(library(tidyr)))
  suppressWarnings(suppressPackageStartupMessages(library(ggplot2)))
  if (is.null(args$alpha_diversity_file) || is.null(args$metadata_file) ||
    is.null(args$class_variable) || is.null(args$output_file)) {
    stop("Missing required arguments for alpha_diversity.")
  }
  if (!file.exists(args$alpha_diversity_file)) {
    stop("Alpha diversity file does not exist.")
  }
  if (!file.exists(args$metadata_file)) {
    stop("Metadata file does not exist.")
  }
  comparisons <- NULL
  if (!is.null(args$comparisons) && nchar(args$comparisons) > 0) {
    # Parse 'A,B;C,D' into list(c('A','B'), c('C','D'))
    comparisons <- strsplit(args$comparisons, ";")[[1]]
    comparisons <- lapply(comparisons, function(x) {
      unlist(strsplit(x, ","))
    })
  }
  alpha_diversity(
    diversity_file = args$alpha_diversity_file,
    metadata_file = args$metadata_file,
    class_variable = args$class_variable,
    comparisons = comparisons,
    output_file = args$output_file
  )
} else if (method == "beta_diversity") {
  suppressWarnings(suppressPackageStartupMessages(library(ggplot2)))
  suppressWarnings(suppressPackageStartupMessages(library(dplyr)))
  suppressWarnings(suppressPackageStartupMessages(library(tibble)))
  suppressWarnings(suppressPackageStartupMessages(library(tidyr)))
  suppressWarnings(suppressPackageStartupMessages(library(ggforce)))
  suppressWarnings(suppressPackageStartupMessages(library(phyloseq)))
  if (is.null(args$beta_diversity_file) || is.null(args$metadata_file) ||
    is.null(args$color_var) || is.null(args$output_file)) {
    stop("Missing required arguments for beta_diversity.")
  }
  if (!file.exists(args$beta_diversity_file)) {
    stop("Beta diversity file does not exist.")
  }
  if (!file.exists(args$metadata_file)) {
    stop("Metadata file does not exist.")
  }
  beta_diversity(
    diversity_file = args$beta_diversity_file,
    metadata_file = args$metadata_file,
    color_var = args$color_var,
    output_file = args$output_file
  )
} else if (method == "differential_abundance") {
  suppressWarnings(suppressPackageStartupMessages(library(DESeq2)))
  suppressWarnings(suppressPackageStartupMessages(library(phyloseq)))
  suppressWarnings(suppressPackageStartupMessages(library(dplyr)))
  suppressWarnings(suppressPackageStartupMessages(library(tibble)))
  if (is.null(args$asv_file) || is.null(args$taxonomy_file) ||
    is.null(args$metadata_file) || is.null(args$taxonomy_level) ||
    is.null(args$class_variable) || is.null(args$group1) ||
    is.null(args$group2) || is.null(args$pv_threshold) ||
    is.null(args$fdr_threshold) || is.null(args$output_prefix)) {
    stop("Missing required arguments for differential_abundance.")
  }
  if (!file.exists(args$asv_file)) {
    stop("ASV file does not exist.")
  }
  if (!file.exists(args$taxonomy_file)) {
    stop("Taxonomy file does not exist.")
  }
  if (!file.exists(args$metadata_file)) {
    stop("Metadata file does not exist.")
  }
  run_deseq2(
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
} else if (method == "picrust_functional") {
  suppressWarnings(suppressPackageStartupMessages(library(DESeq2)))
  suppressWarnings(suppressPackageStartupMessages(library(phyloseq)))
  suppressWarnings(suppressPackageStartupMessages(library(dplyr)))
  suppressWarnings(suppressPackageStartupMessages(library(tibble)))
  if (is.null(args$asv_file) || is.null(args$metadata_file) ||
    is.null(args$class_variable) || is.null(args$group1) ||
    is.null(args$group2) || is.null(args$pv_threshold) ||
    is.null(args$fdr_threshold) || is.null(args$output_prefix)) {
    stop("Missing required arguments for picrust_functional")
  }
  if (!file.exists(args$asv_file)) {
    stop("Picrust input file does not exist.")
  }
  if (!file.exists(args$metadata_file)) {
    stop("Metadata file does not exist.")
  }
  run_deseq2_picrust(
    asv_file = args$asv_file,
    metadata_file = args$metadata_file,
    class_variable = args$class_variable,
    group1 = args$group1,
    group2 = args$group2,
    pv_threshold = args$pv_threshold,
    fdr_threshold = args$fdr_threshold,
    output_prefix = args$output_prefix
  )
} else if (method == "top_freq_plot") {
  suppressWarnings(suppressPackageStartupMessages(library(ggplot2)))
  suppressWarnings(suppressPackageStartupMessages(library(gtools)))
  suppressWarnings(suppressPackageStartupMessages(library(phyloseq)))
  if (is.null(args$deseq2_results_file) || is.null(args$n) ||
    is.null(args$class_variable) || is.null(args$group1) ||
    is.null(args$group2) || is.null(args$output_file)) {
    stop("Missing required arguments for top_freq_plot.")
  }
  if (!file.exists(args$deseq2_results_file)) {
    stop("DESeq2 results file does not exist.")
  }
  deseq2_results <- readRDS(args$deseq2_results_file)
  compute_top_freq_plot(
    deseq2_results = deseq2_results,
    n = args$n,
    class_variable = args$class_variable,
    group1 = args$group1,
    group2 = args$group2,
    output_file = args$output_file
  )
} else if (method == "top_sign_plot") {
  suppressWarnings(suppressPackageStartupMessages(library(ggplot2)))
  suppressWarnings(suppressPackageStartupMessages(library(gtools)))
  suppressWarnings(suppressPackageStartupMessages(library(phyloseq)))
  if (is.null(args$deseq2_results_file) || is.null(args$n) ||
    is.null(args$class_variable) || is.null(args$group1) ||
    is.null(args$group2) || is.null(args$output_file)) {
    stop("Missing required arguments for top_sign_plot.")
  }
  if (!file.exists(args$deseq2_results_file)) {
    stop("DESeq2 results file does not exist.")
  }
  deseq2_results <- readRDS(args$deseq2_results_file)
  compute_top_sign_plot(
    deseq2_results = deseq2_results,
    n = args$n,
    class_variable = args$class_variable,
    group1 = args$group1,
    group2 = args$group2,
    output_file = args$output_file
  )
} else if (method == "top_fc_plot") {
  suppressWarnings(suppressPackageStartupMessages(library(ggplot2)))
  suppressWarnings(suppressPackageStartupMessages(library(gtools)))
  suppressWarnings(suppressPackageStartupMessages(library(phyloseq)))
  if (is.null(args$deseq2_results_file) || is.null(args$n) ||
    is.null(args$class_variable) || is.null(args$group1) ||
    is.null(args$group2) || is.null(args$output_file)) {
    stop("Missing required arguments for top_fc_plot.")
  }
  if (!file.exists(args$deseq2_results_file)) {
    stop("DESeq2 results file does not exist.")
  }
  deseq2_results <- readRDS(args$deseq2_results_file)
  compute_top_fc_plot(
    deseq2_results = deseq2_results,
    n = args$n,
    class_variable = args$class_variable,
    group1 = args$group1,
    group2 = args$group2,
    output_file = args$output_file
  )
} else if (method == "stacked_abundance_barplot") {
  suppressWarnings(suppressPackageStartupMessages(library(ggplot2)))
  suppressWarnings(suppressPackageStartupMessages(library(RColorBrewer)))
  suppressWarnings(suppressPackageStartupMessages(library(ggstatsplot)))
  if (is.null(args$asv_file) || is.null(args$taxonomy_file) ||
    is.null(args$metadata_file) || is.null(args$taxonomy_level) ||
    is.null(args$class_variable) || is.null(args$output_file)) {
    stop("Missing required arguments for stacked_abundance_barplot.")
  }
  if (!file.exists(args$asv_file)) {
    stop("ASV file does not exist.")
  }
  if (!file.exists(args$taxonomy_file)) {
    stop("Taxonomy file does not exist.")
  }
  if (!file.exists(args$metadata_file)) {
    stop("Metadata file does not exist.")
  }
  compute_stacked_abundance_barplot(
    asv_file = args$asv_file,
    taxonomy_file = args$taxonomy_file,
    metadata_file = args$metadata_file,
    taxonomy_level = args$taxonomy_level,
    class_variable = args$class_variable,
    output_file = args$output_file,
    hide_small = ifelse(is.null(args$hide_small), FALSE, args$hide_small),
    rel_abund_file = args$rel_abund_file
  )
} else if (method == "abundance_pie_plot") {
  suppressWarnings(suppressPackageStartupMessages(library(ggpubr)))
  suppressWarnings(suppressPackageStartupMessages(library(RColorBrewer)))
  if (is.null(args$asv_file) || is.null(args$taxonomy_file) ||
    is.null(args$metadata_file) || is.null(args$taxonomy_level) ||
    is.null(args$class_variable) || is.null(args$output_file)) {
    stop("Missing required arguments for abundance_pie_plot.")
  }
  if (!file.exists(args$asv_file)) {
    stop("ASV file does not exist.")
  }
  if (!file.exists(args$taxonomy_file)) {
    stop("Taxonomy file does not exist.")
  }
  if (!file.exists(args$metadata_file)) {
    stop("Metadata file does not exist.")
  }
  compute_abundance_pie_plot(
    asv_file = args$asv_file,
    taxonomy_file = args$taxonomy_file,
    metadata_file = args$metadata_file,
    taxonomy_level = args$taxonomy_level,
    class_variable = args$class_variable,
    output_file = args$output_file,
    rel_abund_file = args$rel_abund_file
  )
} else {
  stop(paste("Unknown method:", method))
}
quit(save = "no")
