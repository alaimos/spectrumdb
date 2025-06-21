<?php

declare(strict_types=1);

return [

    'dashboard_message_1' => 'The Spectrum Data Platform is a powerful tool for managing and analyzing microbiome datasets in the context of plant health. It provides a user-friendly interface for uploading, sharing, and analyzing datasets, along with advanced features for data visualization and collaboration.',
    'dashboard_message_2' => 'To get started, you can upload your own datasets, explore shared datasets, or check out the latest notifications. If you have any questions or need assistance, feel free to reach out to our support team.',
    'dashboard_message_3' => 'We hope you find the Spectrum Data Platform useful for your research and data analysis needs!',

    'explore_introduction' => 'Here you can explore the content of the dataset :name. You can view the data using several analysis targeted to plant microbiomes. Click on the links on the left to navigate to the different sections.',

    'alpha_diversity_description_1' => 'The figure shows boxplots of alpha diversity displaying the distribution of microbial diversity within individual samples across different groups or conditions. The plot shows key statistical measures: the central line represents the median diversity value, while the box boundaries indicate the first and third quartiles (25th and 75th percentiles). The whiskers extend to show the range of typical values, and any points beyond the whiskers represent outliers with unusually high or low diversity.',
    'alpha_diversity_description_2' => 'By comparing boxplots across different groups, you can identify patterns in microbial diversity. Groups with higher median values have greater average diversity, while the height of each box indicates the variability within that group. Overlapping boxes suggest similar diversity levels between groups, whereas non-overlapping boxes may indicate significant differences in microbial community richness and evenness.',

    'beta_diversity_description_1' => 'The figure shows a Principal Coordinate Analysis (PCoA) plot of beta diversity. It visualizes the differences in microbial community composition between samples. Each point on the plot represents a single sample, and the distance between points reflects how similar or different the microbial communities are. Samples that cluster together (appear close to each other) have similar microbial compositions, while samples that are far apart have very different community structures.',
    'beta_diversity_description_2' => 'The plot displays the data in two dimensions, with each axis showing the percentage of variation explained by that particular coordinate. When samples group into distinct clusters, this often indicates that they share similar environmental conditions, treatments, or other factors that influence microbial community assembly. The percentage values on each axis help you understand how much of the total variation in your dataset is captured by the visualization &mdash; higher percentages indicate that more of the community differences are represented in the plot.',

    'diff_abundance_top_significant_description' => 'The plot shows the top :n bacteria in order of statistical significance &mdash; from most significant to least significant &mdash; that display abundance changes between :condition1 and :condition2. The length of each bar is proportional to the magnitude of the observed change. The color indicates the type of change. A red bar represents bacteria with higher abundance in :condition1 compared to :condition2. A blue bar represents bacteria with reduced abundance in :condition1 compared to :condition2. Next to each bar, symbols that encode statistical significance may be present: *** very high significance, ** high significance, * medium significance, . low significance.',
    'diff_abundance_top_fold_change_description' => 'The plot shows the top :n bacteria in order of abundance change &mdash; from most changed to least changed &mdash; between :condition1 and :condition2. The length of each bar is proportional to the magnitude of the observed change. The color indicates the type of change. A red bar represents bacteria with higher abundance in :condition1 compared to :condition2. A blue bar represents bacteria with reduced abundance in :condition1 compared to :condition2. Next to each bar, symbols that encode statistical significance may be present: *** very high significance, ** high significance, * medium significance, . low significance.',
    'diff_abundance_top_frequent_description' => 'The plot shows the top :n bacteria in order of frequency &mdash; from most frequent to least frequent &mdash; that display abundance changes between :condition1 and :condition2. The length of each bar is proportional to the magnitude of the observed change. The color indicates the type of change. A red bar represents bacteria with higher abundance in :condition1 compared to :condition2. A blue bar represents bacteria with reduced abundance in :condition1 compared to :condition2. Next to each bar, symbols that encode statistical significance may be present: *** very high significance, ** high significance, * medium significance, . low significance.',

    'functional_top_significant_description' => 'The plot shows the top :n features in order of statistical significance &mdash; from most significant to least significant &mdash; that display abundance changes between :condition1 and :condition2. The length of each bar is proportional to the magnitude of the observed change. The color indicates the type of change. A red bar represents features with higher abundance in :condition1 compared to :condition2. A blue bar represents features with reduced abundance in :condition1 compared to :condition2. Next to each bar, symbols that encode statistical significance may be present: *** very high significance, ** high significance, * medium significance, . low significance.',
    'functional_top_fold_change_description' => 'The plot shows the top :n features in order of abundance change &mdash; from most changed to least changed &mdash; between :condition1 and :condition2. The length of each bar is proportional to the magnitude of the observed change. The color indicates the type of change. A red bar represents features with higher abundance in :condition1 compared to :condition2. A blue bar represents features with reduced abundance in :condition1 compared to :condition2. Next to each bar, symbols that encode statistical significance may be present: *** very high significance, ** high significance, * medium significance, . low significance.',
    'functional_top_frequent_description' => 'The plot shows the top :n features in order of frequency &mdash; from most frequent to least frequent &mdash; that display abundance changes between :condition1 and :condition2. The length of each bar is proportional to the magnitude of the observed change. The color indicates the type of change. A red bar represents features with higher abundance in :condition1 compared to :condition2. A blue bar represents features with reduced abundance in :condition1 compared to :condition2. Next to each bar, symbols that encode statistical significance may be present: *** very high significance, ** high significance, * medium significance, . low significance.',

    'metadata_inclusion' => [
        'included' => 'Include',
        'excluded' => 'Exclude',
        'default_value' => 'Use Default Value',
    ],

    'sample_selection' => [
        'all' => 'Include All Samples',
        'filtered' => 'Apply Filters',
    ],

    'combine_datasets' => [
        'introduction_title' => 'Combine Multiple Datasets',
        'introduction_description' => 'Create a new dataset by combining samples from multiple existing datasets. You can apply filters to select specific samples, configure metadata pairing, and create a unified dataset for analysis.',
        'step_1_title' => 'Introduction',
        'step_2_title' => 'Dataset Selection',
        'step_3_title' => 'Dataset Details',
        'step_4_title' => 'Metadata Pairing',
        'step_5_title' => 'Review & Confirm',
        'get_started' => 'Get Started',
        'select_datasets' => 'Select Datasets to Combine',
        'sample_filtering' => 'Sample Filtering',
        'all_samples' => 'Include all samples',
        'filtered_samples' => 'Apply filters to select samples',
        'add_filter' => 'Add Filter',
        'remove_filter' => 'Remove Filter',
        'metadata_pairing' => 'Metadata Pairing',
        'pair_automatically' => 'Pair Automatically',
        'default_value_help' => 'This value will be used for samples from datasets that don\'t have this metadata field',
        'follow_steps' => 'Follow the steps below to combine your datasets',
        'select_at_least_2' => 'Select at least 2 datasets to combine and configure sample filtering criteria',
        'selected_datasets' => 'Selected Datasets',
        'add_dataset' => 'Add Dataset',
        'no_datasets_selected' => 'No datasets selected yet.',
        'click_add_dataset' => 'Click "Add Dataset" to start selecting datasets to combine.',
        'select_a_dataset' => 'Select a Dataset',
        'select_a_dataset_placeholder' => 'Select a dataset...',
        'no_conditions_set' => 'No conditions set. All samples will be included.',
        'select_field' => 'Select field...',
        'select_values' => 'Select values...',
        'select_field_first' => 'Select a field first',
        'combined_dataset_details' => 'Combined Dataset Details',
        'configure_name_description' => 'Configure the name, description, and metadata for your combined dataset',
        'dataset_name' => 'Dataset Name',
        'enter_name_for_combined' => 'Enter name for the combined dataset',
        'describe_combined_dataset' => 'Describe the combined dataset',
        'copy_metadata_from_selected' => 'Copy Metadata from Selected Datasets',
        'additional_custom_metadata' => 'Additional Custom Metadata',
        'add_metadata' => 'Add Metadata',
        'no_custom_metadata' => 'No custom metadata added yet.',
        'metadata_key' => 'Metadata key...',
        'metadata_value' => 'Metadata value...',
        'configure_pairing' => 'Configure how sample metadata from different datasets should be paired together',
        'no_metadata_fields_found' => 'No metadata fields found.',
        'go_back_ensure_datasets' => 'Go back to the previous step and ensure datasets are selected.',
        'original_field' => 'Original Field',
        'action' => 'Action',
        'paired_field_name' => 'Paired Field Name',
        'enter_field_name_combined' => 'Enter field name in combined dataset',
        'not_applicable' => 'Not applicable',
        'default_values' => 'Default Values',
        'review_combined_dataset' => 'Review Your Combined Dataset',
        'verify_before_creating' => 'Please verify all information before creating the combined dataset',
        'selected_datasets_review' => 'Selected Datasets',
        'metadata_fields' => 'Metadata Fields',
        'no_metadata_configured' => 'No metadata fields configured',
        'combine_datasets_action' => 'Combine Datasets',
    ],

];
