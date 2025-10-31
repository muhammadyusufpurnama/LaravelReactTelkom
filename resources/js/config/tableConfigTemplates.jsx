// ===================================================================
// Konfigurasi untuk Halaman AnalysisDigitalProduct
// ===================================================================

export const smeTableConfigTemplate = [
    // In Progress
    {
        groupTitle: "In Progress",
        groupClass: "bg-blue-600",
        columnClass: "bg-blue-400",
        columns: [
            { key: "in_progress_n", title: "N" },
            { key: "in_progress_o", title: "O" },
            { key: "in_progress_ae", title: "AE" },
            { key: "in_progress_ps", title: "PS" },
        ],
    },
    // Prov Comp
    {
        groupTitle: "Prov Comp",
        groupClass: "bg-orange-600",
        columnClass: "bg-orange-400",
        subColumnClass: "bg-orange-300",
        columns: [
            {
                key: "prov_comp_n",
                title: "N",
                subColumns: [
                    { key: "_target", title: "T" },
                    { key: "_realisasi", title: "R" },
                    {
                        key: "_percent",
                        title: "P",
                        type: "calculation",
                        calculation: {
                            operation: "percentage",
                            operands: ["prov_comp_n_realisasi", "prov_comp_n_target"],
                        },
                    },
                ],
            },
            {
                key: "prov_comp_o",
                title: "O",
                subColumns: [
                    { key: "_target", title: "T" },
                    { key: "_realisasi", title: "R" },
                    {
                        key: "_percent",
                        title: "P",
                        type: "calculation",
                        calculation: {
                            operation: "percentage",
                            operands: ["prov_comp_o_realisasi", "prov_comp_o_target"],
                        },
                    },
                ],
            },
            {
                key: "prov_comp_ae",
                title: "AE",
                subColumns: [
                    { key: "_target", title: "T" },
                    { key: "_realisasi", title: "R" },
                    {
                        key: "_percent",
                        title: "P",
                        type: "calculation",
                        calculation: {
                            operation: "percentage",
                            operands: ["prov_comp_ae_realisasi", "prov_comp_ae_target"],
                        },
                    },
                ],
            },
            {
                key: "prov_comp_ps",
                title: "PS",
                subColumns: [
                    { key: "_target", title: "T" },
                    { key: "_realisasi", title: "R" },
                    {
                        key: "_percent",
                        title: "P",
                        type: "calculation",
                        calculation: {
                            operation: "percentage",
                            operands: ["prov_comp_ps_realisasi", "prov_comp_ps_target"],
                        },
                    },
                ],
            },
        ],
    },
    // REVENUE
    {
        groupTitle: "REVENUE (Rp Juta)",
        groupClass: "bg-green-700",
        columnClass: "bg-green-500",
        subColumnClass: "bg-green-300",
        columns: [
            {
                key: "revenue_n",
                title: "N",
                subColumns: [
                    { key: "_ach", title: "ACH" },
                    { key: "_target", title: "T" },
                ],
            },
            {
                key: "revenue_o",
                title: "O",
                subColumns: [
                    { key: "_ach", title: "ACH" },
                    { key: "_target", title: "T" },
                ],
            },
            {
                key: "revenue_ae",
                title: "AE",
                subColumns: [
                    { key: "_ach", title: "ACH" },
                    { key: "_target", title: "T" },
                ],
            },
            {
                key: "revenue_ps",
                title: "PS",
                subColumns: [
                    { key: "_ach", title: "ACH" },
                    { key: "_target", title: "T" },
                ],
            },
        ],
    },
    // Grand Total
    {
        groupTitle: "Grand Total",
        groupClass: "bg-gray-600",
        columnClass: "bg-gray-500",
        columns: [
            {
                key: "grand_total_target",
                title: "T",
                type: "calculation",
                calculation: {
                    operation: "sum",
                    operands: ["prov_comp_n_target", "prov_comp_o_target", "prov_comp_ae_target", "prov_comp_ps_target"],
                },
            },
            {
                key: "grand_total_realisasi",
                title: "R",
                type: "calculation",
                calculation: {
                    operation: "sum",
                    operands: ["prov_comp_n_realisasi", "prov_comp_o_realisasi", "prov_comp_ae_realisasi", "prov_comp_ps_realisasi"],
                },
            },
            {
                key: "grand_total_persentase",
                title: "P",
                type: "calculation",
                calculation: {
                    operation: "percentage",
                    operands: ["grand_total_realisasi", "grand_total_target"],
                },
            },
        ],
    },
];

export const legsTableConfigTemplate = [
    {
        groupTitle: "In Progress",
        groupClass: "bg-blue-600",
        columnClass: "bg-blue-400",
        columns: [
            { key: "in_progress_n", title: "N" },
            { key: "in_progress_o", title: "O" },
            { key: "in_progress_ae", title: "AE" },
            { key: "in_progress_ps", title: "PS" },
        ],
    },
    {
        groupTitle: "Prov Comp",
        groupClass: "bg-orange-600",
        columnClass: "bg-orange-400",
        columns: [
            { key: "prov_comp_n_realisasi", title: "N" },
            { key: "prov_comp_o_realisasi", title: "O" },
            { key: "prov_comp_ae_realisasi", title: "AE" },
            { key: "prov_comp_ps_realisasi", title: "PS" },
        ],
    },
    {
        groupTitle: "REVENUE (Rp Juta)",
        groupClass: "bg-green-700",
        columnClass: "bg-green-500",
        subColumnClass: "bg-green-300",
        columns: [
            {
                key: "revenue_n",
                title: "N",
                subColumns: [
                    { key: "_ach", title: "ACH" },
                    { key: "_target", title: "T" },
                ],
            },
            {
                key: "revenue_o",
                title: "O",
                subColumns: [
                    { key: "_ach", title: "ACH" },
                    { key: "_target", title: "T" },
                ],
            },
            {
                key: "revenue_ae",
                title: "AE",
                subColumns: [
                    { key: "_ach", title: "ACH" },
                    { key: "_target", title: "T" },
                ],
            },
            {
                key: "revenue_ps",
                title: "PS",
                subColumns: [
                    { key: "_ach", title: "ACH" },
                    { key: "_target", title: "T" },
                ],
            },
        ],
    },
    {
        groupTitle: "Grand Total",
        groupClass: "bg-gray-600",
        columnClass: "bg-gray-500",
        columns: [
            {
                key: "grand_total_realisasi_legs",
                title: "Total",
                type: "calculation",
                calculation: {
                    operation: "sum",
                    operands: ["prov_comp_n_realisasi", "prov_comp_o_realisasi", "prov_comp_ae_realisasi", "prov_comp_ps_realisasi"],
                },
            },
        ],
    },
];


// ===================================================================
// Konfigurasi untuk Halaman AnalysisSOS
// ===================================================================
const fullSosColumnsTemplate = [
    { Header: 'ID', accessor: 'id' },
    { Header: 'NIPNAS', accessor: 'nipnas' },
    { Header: 'Standard Name', accessor: 'standard_name' },
    { Header: 'Order ID', accessor: 'order_id' },
    { Header: 'Order Subtype', accessor: 'order_subtype' },
    { Header: 'Order Description', accessor: 'order_description' },
    { Header: 'Segmen', accessor: 'segmen' },
    { Header: 'Sub Segmen', accessor: 'sub_segmen' },
    { Header: 'Cust City', accessor: 'cust_city' },
    { Header: 'Cust Witel', accessor: 'cust_witel' },
    { Header: 'Serv City', accessor: 'serv_city' },
    { Header: 'Service Witel', accessor: 'service_witel' },
    { Header: 'Bill Witel', accessor: 'bill_witel' },
    { Header: 'Product Name', accessor: 'li_product_name' },
    { Header: 'Bill Date', accessor: 'li_billdate', type: 'date' },
    { Header: 'Milestone', accessor: 'li_milestone' },
    { Header: 'Kategori', accessor: 'kategori' },
    { Header: 'Status', accessor: 'li_status' },
    { Header: 'Status Date', accessor: 'li_status_date', type: 'date' },
    { Header: 'Is Termin', accessor: 'is_termin' },
    { Header: 'Biaya Pasang', accessor: 'biaya_pasang', type: 'currency' },
    { Header: 'Harga Bulanan', accessor: 'hrg_bulanan', type: 'currency' },
    { Header: 'Revenue', accessor: 'revenue', type: 'currency' },
    { Header: 'Order Created Date', accessor: 'order_created_date', type: 'date' },
    { Header: 'Agree Type', accessor: 'agree_type' },
    { Header: 'Agree Start Date', accessor: 'agree_start_date', type: 'date' },
    { Header: 'Agree End Date', accessor: 'agree_end_date', type: 'date' },
    { Header: 'Lama Kontrak (Hari)', accessor: 'lama_kontrak_hari' },
    { Header: 'Amortisasi', accessor: 'amortisasi' },
    { Header: 'Action CD', accessor: 'action_cd' },
    { Header: 'Kategori Umur', accessor: 'kategori_umur' },
    { Header: 'Umur Order (Hari)', accessor: 'umur_order' },
    { Header: 'Created At', accessor: 'created_at', type: 'date' },
    { Header: 'Updated At', accessor: 'updated_at', type: 'date' },
];

// -- [PERUBAHAN] Menggunakan template master untuk semua tabel detail --
export const provideOrderColumnsTemplate = fullSosColumnsTemplate;
export const inProcessColumnsTemplate = fullSosColumnsTemplate;
export const readyToBillColumnsTemplate = fullSosColumnsTemplate;
export const provCompleteColumnsTemplate = fullSosColumnsTemplate;
