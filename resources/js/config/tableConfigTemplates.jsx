// resources/js/config/tableConfigTemplates.js

// 'export' membuat variabel ini bisa diimpor dan digunakan di file lain.
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
