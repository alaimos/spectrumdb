## SPECTRUM Database

---

### 1. Integrazione Upload Dati Aggiuntivi

- [X] Aggiungere al Job di upload supporto per:
  - [X] Output Picrust
    - [X] KO (KEGG Orthologs)
    - [X] Pathways (KEGG)
    - [X] EC (Enzyme Commission Numbers)
  - [X] Alpha-diversity
    - [X] Faith’s PD
    - [X] Chao1
    - [X] Pielou’s Evenness
  - [X] Beta-diversity
    - [X] Jaccard
    - [X] Bray-Curtis
    - [X] Unweighted UniFrac
    - [X] Weighted UniFrac
- [ ] Integrare l’interfaccia di caricamento con:
  - [ ] Output Picrust
    - [ ] KO (KEGG Orthologs)
    - [ ] Pathways (KEGG)
    - [ ] EC (Enzyme Commission Numbers)
  - [ ] Alpha-diversity
    - [ ] Faith’s PD
    - [ ] Chao1
    - [ ] Pielou’s Evenness
  - [ ] Beta-diversity
    - [ ] Jaccard
    - [ ] Bray-Curtis
    - [ ] Unweighted UniFrac
    - [ ] Weighted UniFrac

---

### 2. Visualizzazione e Esplorazione Dati ("Explore")

- [ ] Costruire pagina “Explore” con:
  - [ ] Tabelle interattive
    - [ ] Output Picrust (KO / EC / Pathway)
    - [ ] Dati tassonomici aggregati per livello (Phylum, Genus, ecc.)
  - [ ] Grafici:
    - [ ] Bar plot dei top-N elementi Picrust
    - [ ] Stacked bar chart / Pie chart per taxa
    - [ ] Boxplot / Violin plot per alpha-diversity
    - [ ] PCoA / NMDS plot per beta-diversity
  - [ ] Analisi di differential abundance
    - [ ] Integrazione con DESeq2 (backend R o via API)
    - [ ] Interfaccia per selezione gruppi, log2FC, FDR, ecc.
  - [ ] Esportazione dati e grafici (CSV, PNG, SVG)

