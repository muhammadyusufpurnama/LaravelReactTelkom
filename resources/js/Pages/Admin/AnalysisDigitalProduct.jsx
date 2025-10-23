import React, { useEffect, useMemo, useState, useCallback } from "react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head, useForm, usePage, router, Link } from "@inertiajs/react";
import InputLabel from "@/Components/InputLabel";
import InputError from "@/Components/InputError";
import PrimaryButton from "@/Components/PrimaryButton";
import NetPriceTable from '@/Components/NetPriceTable';
import CompleteTable from '@/Components/CompleteTable';
import InProgressAnalysisTable from '@/Components/InProgressAnalysisTable';
import QcTable from '@/Components/QCTable';
import HistoryTable from '@/Components/HistoryTable';
import KPIPOAnalysisTable from '@/Components/KPIPOAnalysisTable';
import TableConfigurator from '@/Components/TableConfigurator';
import { smeTableConfigTemplate, legsTableConfigTemplate } from '@/config/tableConfigTemplates';
import EditReportForm from '@/Components/EditReportForm';
import SmeReportTable from '@/Components/SmeReportTable';
import axios from "axios";
import { useSortable } from "@dnd-kit/sortable";
import { CSS } from "@dnd-kit/utilities";
import { debounce } from "lodash";
import toast from "react-hot-toast";
import Swal from 'sweetalert2'; // <-- Tambahkan ini
import withReactContent from 'sweetalert2-react-content';

// ===================================================================
// Helper & Utility Components
// ===================================================================

const MySwal = withReactContent(Swal);

const Pagination = ({ links = [], activeView }) => {
    if (links.length <= 3) return null;

    const appendTabViewToUrl = (url) => {
        if (!url || !activeView) return url;
        try {
            const urlObject = new URL(url);
            urlObject.searchParams.set('tab', activeView);
            return urlObject.toString();
        } catch (error) {
            console.error("URL Pagination tidak valid:", url);
            return url;
        }
    };

    return (
        <div className="flex flex-wrap justify-center items-center mt-4 space-x-1">
            {links.map((link, index) => (
                <Link
                    key={index}
                    href={appendTabViewToUrl(link.url) ?? "#"}
                    className={`px-3 py-2 text-sm border rounded hover:bg-blue-600 hover:text-white transition-colors ${link.active ? "bg-blue-600 text-white" : "bg-white text-gray-700"} ${!link.url ? "text-gray-400 cursor-not-allowed" : ""}`}
                    dangerouslySetInnerHTML={{ __html: link.label }}
                    preserveScroll
                    preserveState
                />
            ))}
        </div>
    );
};

const DetailTabButton = ({ viewName, currentView, children }) => {
    const { filters } = usePage().props;
    const newParams = { ...filters, tab: viewName };
    delete newParams.page; // Kembali ke halaman 1 saat ganti tab

    return (
        <Link
            href={route("admin.analysisDigitalProduct.index", newParams)}
            className={`px-4 py-2 text-sm font-semibold rounded-md transition-colors ${currentView === viewName ? "bg-blue-600 text-white shadow" : "bg-white text-gray-600 hover:bg-gray-100"}`}
            preserveState
            preserveScroll
            replace
        >
            {children}
        </Link>
    );
};

const CollapsibleCard = ({ title, isExpanded, onToggle, children }) => {
    return (
        <div className="bg-white rounded-lg shadow-md overflow-hidden">
            <div
                className="flex justify-between items-center p-4 cursor-pointer bg-gray-50 hover:bg-gray-100"
                onClick={onToggle}
            >
                <h3 className="font-semibold text-gray-700">{title}</h3>
                <svg
                    className={`w-5 h-5 text-gray-500 transform transition-transform ${isExpanded ? "rotate-180" : ""}`}
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        strokeWidth={2}
                        d="M19 9l-7 7-7-7"
                    />
                </svg>
            </div>
            {isExpanded && (
                <div className="p-6 space-y-6 border-t border-gray-200">
                    {children}
                </div>
            )}
        </div>
    );
};

const DetailsCard = ({ totals, segment, period }) => (
    <div className="bg-white p-6 rounded-lg shadow-md">
        <h3 className="font-semibold text-lg text-gray-800 mb-4">Details</h3>
        <div className="space-y-2 text-sm">
            <div className="flex justify-between">
                <span>Total</span>
                <span>{totals.total}</span>
            </div>
            <div className="flex justify-between">
                <span>OGP</span>
                <span>{totals.ogp}</span>
            </div>
            <div className="flex justify-between">
                <span>Closed</span>
                <span>{totals.closed}</span>
            </div>
            <div className="flex justify-between">
                <span>Segment</span>
                <span className="font-bold">{segment}</span>
            </div>
            <div className="flex justify-between">
                <span>Period</span>
                <span className="font-bold">{period}</span>
            </div>
        </div>
    </div>
);

const ProgressBar = ({ progress, text }) => (
    <div className="mt-4">
        <p className="text-sm font-semibold text-gray-700 mb-1">
            {text} {progress}%
        </p>
        <div className="w-full bg-gray-200 rounded-full">
            <div
                className="bg-blue-600 text-xs font-medium text-blue-100 text-center p-0.5 leading-none rounded-full transition-all duration-300"
                style={{ width: `${progress}%` }}
            ></div>
        </div>
    </div>
);

const AgentFormModal = ({ isOpen, onClose, agent }) => {
    const { data, setData, post, put, processing, errors, reset } = useForm({
        name: "",
        display_witel: "",
        filter_witel_lama: "",
        special_filter_column: "",
        special_filter_value: "",
    });

    useEffect(() => {
        if (agent) {
            setData({
                name: agent.name || "",
                display_witel: agent.display_witel || "",
                filter_witel_lama: agent.filter_witel_lama || "",
                special_filter_column: agent.special_filter_column || "",
                special_filter_value: agent.special_filter_value || "",
            });
        } else {
            reset();
        }
    }, [agent, isOpen]);

    const handleSubmit = (e) => {
        e.preventDefault();
        const onSuccess = () => {
            onClose();
        };

        if (agent) {
            put(route("admin.account-officers.update", agent.id), {
                onSuccess,
                preserveScroll: true,
            });
        } else {
            post(route("admin.account-officers.store"), {
                onSuccess,
                preserveScroll: true,
            });
        }
    };

    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 bg-black bg-opacity-50 z-50 flex justify-center items-center p-4">
            <div className="bg-white p-6 rounded-lg shadow-xl w-full max-w-md">
                <h2 className="text-lg font-bold mb-4">
                    {agent ? "Edit Agen" : "Tambah Agen Baru"}
                </h2>
                <form onSubmit={handleSubmit} className="space-y-4">
                    <div>
                        <InputLabel htmlFor="name" value="Nama PO" />
                        <input
                            id="name"
                            type="text"
                            value={data.name}
                            onChange={(e) => setData("name", e.target.value)}
                            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                            required
                        />
                        <InputError message={errors.name} className="mt-2" />
                    </div>
                    <div>
                        <InputLabel
                            htmlFor="display_witel"
                            value="Display Witel"
                        />
                        <input
                            id="display_witel"
                            type="text"
                            value={data.display_witel}
                            onChange={(e) =>
                                setData("display_witel", e.target.value)
                            }
                            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                            required
                        />
                        <InputError
                            message={errors.display_witel}
                            className="mt-2"
                        />
                    </div>
                    <div>
                        <InputLabel
                            htmlFor="filter_witel_lama"
                            value="Filter Witel Lama (sesuai data mentah)"
                        />
                        <input
                            id="filter_witel_lama"
                            type="text"
                            value={data.filter_witel_lama}
                            onChange={(e) =>
                                setData("filter_witel_lama", e.target.value)
                            }
                            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                            required
                        />
                        <InputError
                            message={errors.filter_witel_lama}
                            className="mt-2"
                        />
                    </div>
                    <div>
                        <InputLabel
                            htmlFor="special_filter_column"
                            value="Filter Kolom Khusus (opsional)"
                        />
                        <input
                            id="special_filter_column"
                            type="text"
                            value={data.special_filter_column}
                            onChange={(e) =>
                                setData("special_filter_column", e.target.value)
                            }
                            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                        />
                        <InputError
                            message={errors.special_filter_column}
                            className="mt-2"
                        />
                    </div>
                    <div>
                        <InputLabel
                            htmlFor="special_filter_value"
                            value="Nilai Filter Kolom Khusus (opsional)"
                        />
                        <input
                            id="special_filter_value"
                            type="text"
                            value={data.special_filter_value}
                            onChange={(e) =>
                                setData("special_filter_value", e.target.value)
                            }
                            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                        />
                        <InputError
                            message={errors.special_filter_value}
                            className="mt-2"
                        />
                    </div>

                    <div className="flex items-center justify-end gap-4 pt-4">
                        <button
                            type="button"
                            onClick={onClose}
                            className="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300"
                        >
                            Batal
                        </button>
                        <PrimaryButton type="submit" disabled={processing}>
                            {processing ? "Menyimpan..." : "Simpan"}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    );
};

const CustomTargetForm = ({
    tableConfig,
    witelList,
    initialData,
    period,
    segment,
}) => {
    const [isExpanded, setIsExpanded] = useState(false);

    const customTargetColumns = useMemo(() => {
        const targets = [];
        tableConfig.forEach((group) => {
            group.columns.forEach((col) => {
                if (col.type === "target") {
                    targets.push({ key: col.key, title: col.title });
                }
            });
        });
        return targets;
    }, [tableConfig]);

    const { data, setData, post, processing, errors } = useForm({
        targets: {},
        period: period,
        segment: segment,
    });

    useEffect(() => {
        setData("targets", initialData || {});
    }, [initialData]);

    const handleInputChange = (targetKey, witel, value) => {
        setData("targets", {
            ...data.targets,
            [targetKey]: {
                ...data.targets[targetKey],
                [witel]: value,
            },
        });
    };

    function submit(e) {
        e.preventDefault();
        post(route("admin.analysisDigitalProduct.saveCustomTargets"), {
            preserveScroll: true,
        });
    }

    if (customTargetColumns.length === 0) {
        return null;
    }

    return (
        <form
            onSubmit={submit}
            className="bg-white p-6 rounded-lg shadow-md text-sm"
        >
            <div
                className="flex justify-between items-center cursor-pointer mb-4"
                onClick={() => setIsExpanded(!isExpanded)}
            >
                <h3 className="font-semibold text-lg text-gray-800">
                    Edit Target Kustom
                </h3>
                <button
                    type="button"
                    className="text-blue-600 text-sm font-bold hover:underline p-2"
                >
                    {isExpanded ? "Minimize" : "Expand"}
                </button>
            </div>

            {isExpanded && (
                <div className="mt-4 space-y-6">
                    {customTargetColumns.map((col) => (
                        <fieldset
                            key={col.key}
                            className="border rounded-md p-3"
                        >
                            <legend className="text-base font-semibold px-2">
                                {col.title}
                            </legend>
                            {/* [PERBAIKAN] Menggunakan kelas grid responsif */}
                            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 p-2">
                                {witelList.map((witel) => (
                                    <div key={witel}>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            {witel}
                                        </label>
                                        <input
                                            type="number"
                                            step="0.01"
                                            value={
                                                data.targets[col.key]?.[
                                                witel
                                                ] ?? ""
                                            }
                                            onChange={(e) =>
                                                handleInputChange(
                                                    col.key,
                                                    witel,
                                                    e.target.value,
                                                )
                                            }
                                            className="p-1 border rounded w-full"
                                            placeholder="0"
                                        />
                                    </div>
                                ))}
                            </div>
                        </fieldset>
                    ))}
                    <button
                        type="submit"
                        disabled={processing}
                        className="w-full mt-4 px-4 py-2 bg-blue-600 text-white font-semibold rounded-md hover:bg-blue-700 disabled:bg-blue-400"
                    >
                        {processing ? "Menyimpan..." : "Simpan Target Kustom"}
                    </button>
                </div>
            )}
        </form>
    );
};

// ===================================================================
// Main Page Component
// ===================================================================
// GANTI SELURUH FUNGSI KOMPONEN UTAMA ANDA DENGAN INI
export default function AnalysisDigitalProduct({
    auth,
    savedTableConfig,
    reportData = [],
    currentSegment = "SME",
    period = "",
    inProgressData = { data: [], links: [] },
    completeData = { data: [], links: [] },
    historyData = { data: [], links: [], total: 0 },
    accountOfficers = [],
    netPriceData = { data: [], links: [] },
    kpiData = [],
    qcData = { data: [], links: [] },
    currentInProgressYear,
    initialFilters = {},
    flash = {},
    errors: pageErrors = {},
    customTargets = {},
    tabCounts = { inprogress: 0, complete: 0, qc: 0, history: 0, netprice: 0 },
}) {
    const { props } = usePage();
    const { filters } = props;

    const activeDetailView = filters.tab || 'inprogress';

    // const [localFilters, setLocalFilters] = useState({
    //     period: filters.period || '',
    //     segment: filters.segment || 'SME',
    // });

    useEffect(() => {
        if (flash.success) toast.success(flash.success);
        if (flash.error) toast.error(flash.error);
    }, [flash]);

    // useEffect(() => {
    //     // Gunakan debounce untuk mencegah request berlebihan jika user mengganti filter dengan cepat
    //     const debouncedFilter = debounce(() => {
    //         const query = { ...filters, ...localFilters }; // Gabungkan filter lama dan baru

    //         // Hapus parameter kosong agar URL bersih
    //         Object.keys(query).forEach(key => {
    //             if (query[key] === '' || query[key] === null || query[key] === undefined) {
    //                 delete query[key];
    //             }
    //         });

    //         router.get(route('admin.analysisDigitalProduct.index'), query, {
    //             preserveState: true,
    //             preserveScroll: true,
    //             replace: true,
    //         });
    //     }, 300); // Tunggu 300ms setelah user berhenti mengubah filter

    //     debouncedFilter();

    //     // Cleanup function untuk debounce
    //     return () => debouncedFilter.cancel();

    // }, [localFilters]);

    useEffect(() => {
        if (flash.success) {
            toast.success(flash.success);
        }
        if (flash.error) {
            toast.error(flash.error);
        }
    }, [flash]);

    const [tableConfig, setTableConfig] = useState(
        currentSegment === 'SME' ? smeTableConfigTemplate : legsTableConfigTemplate
    );

    // useEffect untuk SINKRONISASI state dengan props dari server dan localStorage
    useEffect(() => {
        const storageKey = `userTableConfig_${currentSegment}`;
        const defaultConfig = currentSegment === 'SME' ? smeTableConfigTemplate : legsTableConfigTemplate;

        // [LOGIKA UTAMA] Jika server mengirimkan konfigurasi, maka itu adalah sumber kebenaran.
        if (savedTableConfig && Array.isArray(savedTableConfig) && savedTableConfig.length > 0) {
            console.log(`[SYNC] Menggunakan konfigurasi dari database untuk segmen: ${currentSegment}`);
            setTableConfig(savedTableConfig);

            // Selalu sinkronkan localStorage dengan data terbaru dari server
            localStorage.setItem(storageKey, JSON.stringify(savedTableConfig));
        }
        // [LOGIKA FALLBACK] Jika server TIDAK mengirimkan konfigurasi (setelah reset atau saat pertama kali),
        // maka kita WAJIB menggunakan template default.
        else {
            console.log(`[SYNC] Menggunakan konfigurasi default untuk segmen: ${currentSegment}`);
            setTableConfig(defaultConfig);

            // [PENTING] Hapus kunci localStorage yang lama untuk mencegah masalah ini terjadi lagi.
            localStorage.removeItem(storageKey);
        }

        // Dependency array ini sudah benar, jangan diubah.
    }, [currentSegment, savedTableConfig]);  // <-- KUNCI UTAMA: Jalankan efek ini setiap kali segmen atau data dari server berubah

    // useEffect untuk MENYIMPAN perubahan lokal ke localStorage (TETAP DIPERLUKAN)
    useEffect(() => {
        // Hindari menimpa localStorage dengan config default saat komponen baru dimuat
        // Cek jika tableConfig bukan template awal
        const isDefaultSme = JSON.stringify(tableConfig) === JSON.stringify(smeTableConfigTemplate);
        const isDefaultLegs = JSON.stringify(tableConfig) === JSON.stringify(legsTableConfigTemplate);

        if (!isDefaultSme && !isDefaultLegs) {
            const storageKey = `userTableConfig_${currentSegment}`;
            localStorage.setItem(storageKey, JSON.stringify(tableConfig));
            console.log(`Perubahan disimpan ke localStorage untuk segmen: ${currentSegment}`);
        }
    }, [tableConfig, currentSegment]);

    const handleSaveConfig = () => {
        const pageName = `analysis_digital_${currentSegment.toLowerCase()}`;

        router.post(
            route("admin.analysisDigitalProduct.saveConfig"),
            {
                configuration: tableConfig,
                page_name: pageName,
            },
            {
                preserveScroll: true,
                // TAMBAHKAN KODE DI BAWAH INI
                onSuccess: () => {
                    console.log("SUCCESS: Server merespons dengan sukses.");
                    toast.success("Tampilan tabel berhasil disimpan!");
                    localStorage.removeItem(
                        `userTableConfig_${currentSegment}`,
                    );
                },
                onError: (errors) => {
                    console.error("ERROR: Server mengembalikan error.", errors);
                    toast.error("Gagal menyimpan. Cek konsol untuk detail.");
                },
                // SAMPAI SINI
            },
        );
    };

    const [search, setSearch] = useState(filters.search || "");
    const [decimalPlaces, setDecimalPlaces] = useState(2);
    const [selectedWitel, setSelectedWitel] = useState(filters.witel || "");

    const witelList = [
        "BALI",
        "JATIM BARAT",
        "JATIM TIMUR",
        "NUSA TENGGARA",
        "SURAMADU",
    ];

    const handleExportReport = () => {
        const form = document.createElement("form");
        form.method = "POST";
        form.action = route("admin.analysisDigitalProduct.export.report");
        form.style.display = "none";

        const csrfToken = document
            .querySelector('meta[name="csrf-token"]')
            .getAttribute("content");
        const csrfInput = document.createElement("input");
        csrfInput.type = "hidden";
        csrfInput.name = "_token";
        csrfInput.value = csrfToken;
        form.appendChild(csrfInput);

        const segmentInput = document.createElement("input");
        segmentInput.type = "hidden";
        segmentInput.name = "segment";
        segmentInput.value = currentSegment;
        form.appendChild(segmentInput);

        const periodInput = document.createElement("input");
        periodInput.type = "hidden";
        periodInput.name = "period";
        periodInput.value = period;
        form.appendChild(periodInput);

        const detailsInput = document.createElement("input");
        detailsInput.type = "hidden";
        detailsInput.name = "details";
        detailsInput.value = JSON.stringify(detailsTotals);
        form.appendChild(detailsInput);

        const configInput = document.createElement("input");
        configInput.type = "hidden";
        configInput.name = "table_config";
        configInput.value = JSON.stringify(tableConfig);
        form.appendChild(configInput);

        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    };

    const [progressStates, setProgressStates] = useState({
        upload: null,
        mentah: null,
        complete: null,
        cancel: null,
    });

    useEffect(() => {
        const urlParams = new URLSearchParams(window.location.search);
        const batchId = urlParams.get("batch_id");
        const jobType = urlParams.get("job_type");

        // Fungsi untuk membersihkan URL setelah selesai
        const cleanUrl = () => {
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.delete("batch_id");
            currentUrl.searchParams.delete("job_type");
            window.history.replaceState({}, document.title, currentUrl.toString());
        };

        // Gunakan kondisi 'if' yang lebih sederhana dan andal dari kode lama Anda
        if (batchId && jobType && progressStates[jobType] === null) {

            // Langsung set progress ke 0 agar progress bar muncul
            setProgressStates((prev) => ({ ...prev, [jobType]: 0 }));

            const interval = setInterval(() => {
                axios.get(route("import.progress", { batchId }))
                    .then((response) => {
                        const progress = response.data.progress ?? 0;
                        setProgressStates((prev) => ({ ...prev, [jobType]: progress }));

                        if (progress >= 100) {
                            clearInterval(interval);
                            setTimeout(() => {
                                setProgressStates((prev) => ({ ...prev, [jobType]: null }));
                                cleanUrl();
                                router.reload({
                                    preserveScroll: true,
                                    // Anda bisa sesuaikan 'only' jika perlu
                                });
                            }, 1500); // Beri jeda 1.5 detik
                        }
                    })
                    .catch((error) => {
                        console.error("Gagal mengambil progres job:", error);
                        clearInterval(interval);
                        setProgressStates((prev) => ({ ...prev, [jobType]: null }));
                        cleanUrl();
                    });
            }, 1000); // Interval polling setiap 1 detik

            return () => clearInterval(interval);
        }
    }, []); // Dependency array kosong agar hanya berjalan sekali saat mount


    const {
        data: uploadData,
        setData: setUploadData,
        post: postUpload,
        processing,
        errors,
        cancel,
    } = useForm({ document: null });
    const {
        data: completeDataForm,
        setData: setCompleteDataForm,
        post: postComplete,
        processing: completeProcessing,
        errors: completeErrors,
        reset: completeReset,
    } = useForm({ complete_document: null });
    const {
        data: cancelDataForm,
        setData: setCancelDataForm,
        post: postCancel,
        processing: cancelProcessing,
        errors: cancelErrors,
        reset: cancelReset,
    } = useForm({ cancel_document: null });

    const submitCompleteFile = (e) => {
        e.preventDefault();
        postComplete(route("admin.analysisDigitalProduct.uploadComplete"), {
            onSuccess: () => {
                completeReset("complete_document");
            },
        });
    };

    const submitCancelFile = (e) => {
        e.preventDefault();
        postCancel(route("admin.analysisDigitalProduct.uploadCancel"), {
            onSuccess: () => cancelReset("cancel_document"),
        });
    };

    const handleSyncCompleteClick = () => {
        if (
            confirm(
                "Anda yakin ingin menjalankan sinkronisasi data order complete?",
            )
        ) {
            router.post(
                route("admin.analysisDigitalProduct.syncCompletedOrders"),
                {},
                {
                    preserveScroll: true,
                },
            );
        }
    };
    const handleSyncCancelClick = () => {
        if (
            confirm(
                "Anda yakin ingin menjalankan proses sinkronisasi untuk mengubah status order menjadi CANCEL?",
            )
        ) {
            router.post(
                route("admin.analysisDigitalProduct.syncCancel"),
                {},
                { preserveScroll: true },
            );
        }
    };

    const [isModalOpen, setIsModalOpen] = useState(false);
    const [editingAgent, setEditingAgent] = useState(null);
    const [isCompleteSectionExpanded, setIsCompleteSectionExpanded] =
        useState(false);
    const [isCancelSectionExpanded, setIsCancelSectionExpanded] =
        useState(false);

    const openModal = (agent = null) => {
        setEditingAgent(agent);
        setIsModalOpen(true);
    };
    const closeModal = () => {
        setIsModalOpen(false);
        setEditingAgent(null);
    };

    const handleFilterChange = (newFilters) => {
        const query = {
            search: search,
            segment: currentSegment,
            period: period,
            in_progress_year: currentInProgressYear,
            witel: selectedWitel,
            ...newFilters,
        };

        Object.keys(query).forEach((key) => {
            if (
                query[key] === "" ||
                query[key] === null ||
                query[key] === undefined
            ) {
                delete query[key];
            }
        });

        router.get(route("admin.analysisDigitalProduct.index"), query, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const handleSearch = (e) => {
        e.preventDefault();
        handleFilterChange({ search: search, page: 1 });
    };

    function handleSegmentChange(e) {
        handleFilterChange({ segment: e.target.value, page: 1 });
    }
    function handlePeriodChange(e) {
        handleFilterChange({ period: e.target.value, page: 1 });
    }
    function handleInProgressYearChange(e) {
        handleFilterChange({ in_progress_year: e.target.value, page: 1 });
    }

    function handleUploadSubmit(e) {
        e.preventDefault();
        postUpload(route("admin.analysisDigitalProduct.upload"));
    }

    const exportUrl = useMemo(() => {
        const params = new URLSearchParams({
            segment: currentSegment,
            in_progress_year: currentInProgressYear,
        });
        if (selectedWitel) {
            params.append("witel", selectedWitel);
        }
        return `${route("admin.analysisDigitalProduct.export.inprogress")}?${params.toString()}`;
    }, [currentSegment, currentInProgressYear, selectedWitel]);

    function handleWitelChange(e) {
        const newWitel = e.target.value;
        setSelectedWitel(newWitel);
        handleFilterChange({ witel: newWitel, page: 1 });
    }

    const detailsTotals = useMemo(() => {
        if (!reportData || reportData.length === 0)
            return { ogp: 0, closed: 0, total: 0 };
        const totals = reportData.reduce(
            (acc, item) => {
                const ogp =
                    (Number(item.in_progress_n) || 0) +
                    (Number(item.in_progress_o) || 0) +
                    (Number(item.in_progress_ae) || 0) +
                    (Number(item.in_progress_ps) || 0);
                const closed =
                    (Number(item.prov_comp_n_realisasi) || 0) +
                    (Number(item.prov_comp_o_realisasi) || 0) +
                    (Number(item.prov_comp_ae_realisasi) || 0) +
                    (Number(item.prov_comp_ps_realisasi) || 0);
                acc.ogp += ogp;
                acc.closed += closed;
                return acc;
            },
            { ogp: 0, closed: 0 },
        );
        return { ...totals, total: totals.ogp + totals.closed };
    }, [reportData, currentSegment]);

    const generatePeriodOptions = () => {
        const options = [];
        let date = new Date();
        date.setDate(1);
        for (let i = 0; i < 24; i++) {
            const year = date.getFullYear();
            const month = (date.getMonth() + 1).toString().padStart(2, "0");
            const value = `${year}-${month}`;
            const label = date.toLocaleString("id-ID", {
                month: "long",
                year: "numeric",
            });
            options.push(
                <option key={value} value={value}>
                    {label}
                </option>,
            );
            date.setMonth(date.getMonth() - 1);
        }
        return options;
    };

    const generateYearOptions = () => {
        const options = [];
        const currentYear = new Date().getFullYear();
        for (let i = 0; i < 5; i++) {
            const year = currentYear - i;
            options.push(
                <option key={year} value={year}>
                    {year}
                </option>,
            );
        }
        return options;
    };

    const handleClearHistory = async () => {
        const result = await MySwal.fire({
            title: 'Anda Yakin?',
            text: "Anda akan menghapus seluruh data histori. Aksi ini tidak dapat dibatalkan!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, hapus semua!',
            cancelButtonText: 'Batal'
        });

        if (result.isConfirmed) {
            router.post(
                route("admin.analysisDigitalProduct.clearHistory"),
                {},
                {
                    preserveScroll: true,
                    onSuccess: () => {
                        toast.success("Seluruh histori berhasil dihapus.");
                    },
                    onError: () => {
                        toast.error("Gagal menghapus histori.");
                    },
                },
            );
        }
    };

    return (
        <AuthenticatedLayout auth={auth} header="Analysis Digital Product">
            <Head title="Analysis Digital Product" />

            <div className="grid grid-cols-1 lg:grid-cols-4 gap-6">
                <div className="lg:col-span-3 space-y-6">
                    <TableConfigurator
                        tableConfig={tableConfig}
                        setTableConfig={setTableConfig}
                        currentSegment={currentSegment}
                        onSave={handleSaveConfig}
                    />

                    <div className="bg-white p-6 rounded-lg shadow-md">
                        <div className="flex flex-wrap justify-between items-center mb-4 gap-4">
                            <h3 className="font-semibold text-lg text-gray-800">
                                Data Report
                            </h3>
                            <div className="flex flex-wrap items-center justify-start sm:justify-end gap-4 w-full sm:w-auto">
                                <button
                                    onClick={handleExportReport}
                                    className="px-4 py-2 text-sm font-bold text-white bg-green-600 rounded-md hover:bg-green-700 whitespace-nowrap"
                                >
                                    Ekspor Excel
                                </button>
                                <div className="flex items-center gap-2">
                                    <label
                                        htmlFor="decimal_places"
                                        className="text-sm font-medium text-gray-600"
                                    >
                                        Desimal:
                                    </label>
                                    <input
                                        id="decimal_places"
                                        type="number"
                                        min="0"
                                        max="10"
                                        value={decimalPlaces}
                                        onChange={(e) =>
                                            setDecimalPlaces(
                                                Number(e.target.value),
                                            )
                                        }
                                        className="border border-gray-300 rounded-md text-sm p-2 w-20"
                                    />
                                </div>
                                <select
                                    value={filters.period || ''} // Gunakan filters dari props
                                    onChange={(e) => router.get(route('admin.analysisDigitalProduct.index'), {
                                        ...filters, // Pertahankan filter yang sudah ada
                                        period: e.target.value, // Set nilai baru
                                        page: 1 // Wajib reset ke halaman 1 saat filter berubah
                                    }, { preserveState: true, replace: true })}
                                    className="border border-gray-300 rounded-md text-sm p-2"
                                >
                                    {generatePeriodOptions()}
                                </select>
                                <select
                                    value={filters.segment || 'SME'} // Gunakan filters dari props
                                    onChange={(e) => router.get(route('admin.analysisDigitalProduct.index'), {
                                        ...filters, // Pertahankan filter yang sudah ada
                                        segment: e.target.value, // Set nilai baru
                                        page: 1 // Wajib reset ke halaman 1 saat filter berubah
                                    }, { preserveState: true, replace: true })}
                                    className="border border-gray-300 rounded-md text-sm p-2"
                                >
                                    <option value="LEGS">LEGS</option>
                                    <option value="SME">SME</option>
                                </select>
                            </div>
                        </div>
                        <SmeReportTable
                            data={reportData}
                            decimalPlaces={decimalPlaces}
                            tableConfig={tableConfig}
                            setTableConfig={setTableConfig}
                        />
                    </div>

                    <div className="bg-white p-6 rounded-lg shadow-md">
                        <div className="flex flex-wrap justify-between items-center mb-4 gap-4">
                            <div className="flex flex-wrap items-center gap-2 border p-1 rounded-lg bg-gray-50">
                                <DetailTabButton viewName="inprogress" currentView={activeDetailView}>In Progress ({tabCounts.inprogress})</DetailTabButton>
                                <DetailTabButton viewName="complete" currentView={activeDetailView}>Complete ({tabCounts.complete})</DetailTabButton>
                                <DetailTabButton viewName="qc" currentView={activeDetailView}>QC ({tabCounts.qc})</DetailTabButton>
                                <DetailTabButton viewName="history" currentView={activeDetailView}>History ({tabCounts.history})</DetailTabButton>
                                <DetailTabButton viewName="netprice" currentView={activeDetailView}>Net Price ({tabCounts.netprice})</DetailTabButton>
                                <DetailTabButton viewName="kpi" currentView={activeDetailView}>KPI PO</DetailTabButton>
                            </div>

                            {activeDetailView === "netprice" && (
                                <select
                                    value={filters.net_price_status || ''}
                                    onChange={(e) => router.get(route('admin.analysisDigitalProduct.index'), {
                                        ...filters, // Pertahankan filter yang ada
                                        net_price_status: e.target.value, // Set nilai filter baru
                                        tab: activeDetailView, // <-- INI KUNCINYA: pastikan tab tetap 'netprice'
                                        page: 1 // Selalu kembali ke halaman 1 saat filter diubah
                                    }, { preserveState: true, replace: true })}
                                    className="border border-gray-300 rounded-md text-sm p-2"
                                >
                                    <option value="">Semua Harga</option>
                                    <option value="template">Harga Template</option>
                                    <option value="pasti">Harga Pasti</option>
                                </select>
                            )}
                            {(activeDetailView === "inprogress" ||
                                activeDetailView === "complete" ||
                                activeDetailView === "qc" ||
                                activeDetailView === "netprice") && (
                                    <div className="flex items-center gap-4 flex-wrap">
                                        <form
                                            onSubmit={(e) => {
                                                e.preventDefault();
                                                router.get(route('admin.analysisDigitalProduct.index'), {
                                                    ...filters, // Pertahankan semua filter yang sudah ada
                                                    search: search, // Tambahkan/update kata kunci pencarian
                                                    tab: activeDetailView, // <-- INI KUNCINYA: pastikan tab tetap sama
                                                    page: 1 // Selalu kembali ke halaman 1 saat melakukan pencarian baru
                                                }, { preserveState: true, replace: true, preserveScroll: true });
                                            }}
                                            className="flex items-center gap-2"
                                        >
                                            <input
                                                type="text"
                                                value={search}
                                                onChange={(e) => setSearch(e.target.value)}
                                                placeholder="Cari Order ID..."
                                                className="border border-gray-300 rounded-md text-sm p-2 w-48"
                                            />
                                            <PrimaryButton type="submit">
                                                Cari
                                            </PrimaryButton>
                                        </form>
                                    </div>
                                )}
                            {activeDetailView === "inprogress" && (
                                <div className="flex flex-col sm:flex-row items-stretch sm:items-center gap-4">
                                    <select
                                        value={selectedWitel}
                                        onChange={handleWitelChange}
                                        className="border border-gray-300 rounded-md text-sm p-2"
                                    >
                                        <option value="">Semua Witel</option>
                                        {witelList.map((w) => (
                                            <option key={w} value={w}>
                                                {w}
                                            </option>
                                        ))}
                                    </select>
                                    <select
                                        value={currentInProgressYear}
                                        onChange={handleInProgressYearChange}
                                        className="border border-gray-300 rounded-md text-sm p-2"
                                    >
                                        {generateYearOptions()}
                                    </select>
                                    <a
                                        href={exportUrl}
                                        className="px-3 py-2 text-sm font-bold text-white bg-green-600 rounded-md hover:bg-green-700"
                                    >
                                        Export Excel
                                    </a>
                                </div>
                            )}
                            {activeDetailView === "history" && (
                                <div className="w-full md:w-auto flex items-center gap-2">
                                    <a
                                        href={route("admin.analysisDigitalProduct.export.history")}
                                        className="w-full px-4 py-2 text-sm font-bold text-white bg-green-600 rounded-md hover:bg-green-700 focus:outline-none"
                                    >
                                        Export Excel
                                    </a>
                                    <button onClick={handleClearHistory} className="w-full px-4 py-2 text-sm font-bold text-white bg-red-600 rounded-md hover:bg-red-700 focus:outline-none">
                                        Clear History
                                    </button>
                                </div>
                            )}
                            {activeDetailView === "kpi" && (
                                <div className="w-full md:w-auto">
                                    <a
                                        href={route("admin.analysisDigitalProduct.export.kpiPo")}
                                        className="inline-block px-4 py-2 text-sm font-bold text-white bg-green-600 rounded-md hover:bg-green-700"
                                    >
                                        Ekspor Excel
                                    </a>
                                </div>
                            )}
                        </div>

                        {activeDetailView === "inprogress" && (
                            <InProgressAnalysisTable dataPaginator={inProgressData} activeView={activeDetailView} />
                        )}
                        {activeDetailView === "complete" && (
                            <CompleteTable dataPaginator={completeData} activeView={activeDetailView} />
                        )}
                        {activeDetailView === "history" && (
                            <HistoryTable historyData={historyData} activeView={activeDetailView} />
                        )}
                        {activeDetailView === "qc" && (
                            <QcTable dataPaginator={qcData} activeView={activeDetailView} />
                        )}
                        {activeDetailView === "netprice" && (
                            <NetPriceTable dataPaginator={netPriceData} activeView={activeDetailView} />
                        )}
                        {activeDetailView === "kpi" && (
                            <KPIPOAnalysisTable
                                data={kpiData}
                                accountOfficers={accountOfficers}
                                openModal={openModal}
                            />
                        )}
                    </div>
                </div>

                <div className="lg:col-span-1 space-y-6">
                    <DetailsCard
                        totals={detailsTotals}
                        segment={currentSegment}
                        period={new Date(period + "-02").toLocaleString(
                            "id-ID",
                            { month: "long", year: "numeric" },
                        )}
                    />
                    <div className="bg-white p-6 rounded-lg shadow-md">
                        <h3 className="font-semibold text-lg text-gray-800">
                            Unggah Data Mentah
                        </h3>
                        <p className="text-gray-500 mt-1 text-sm">
                            Unggah Dokumen (xlsx, xls, csv) untuk memperbarui
                            data.
                        </p>
                        <form
                            onSubmit={handleUploadSubmit}
                            className="mt-4 space-y-4"
                        >
                            <div>
                                <input
                                    type="file"
                                    className="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                                    onChange={(e) =>
                                        setUploadData(
                                            "document",
                                            e.target.files[0],
                                        )
                                    }
                                    disabled={processing}
                                />
                                {errors.document && (
                                    <p className="text-red-500 text-xs mt-1">
                                        {errors.document}
                                    </p>
                                )}
                            </div>
                            {progressStates.mentah !== null && (
                                <ProgressBar
                                    progress={progressStates.mentah}
                                    text="Memproses file..."
                                />
                            )}
                            <div className="flex items-center gap-4">
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="px-4 py-2 bg-blue-600 text-white font-semibold rounded-md hover:bg-blue-700 disabled:bg-blue-400"
                                >
                                    {processing
                                        ? "Mengunggah..."
                                        : "Unggah Dokumen"}
                                </button>
                                {processing && (
                                    <button
                                        type="button"
                                        onClick={() => cancel()}
                                        className="px-4 py-2 bg-red-600 text-white font-semibold rounded-md hover:bg-red-700"
                                    >
                                        Batal
                                    </button>
                                )}
                            </div>
                        </form>
                    </div>
                    <CustomTargetForm
                        tableConfig={tableConfig}
                        witelList={witelList}
                        initialData={customTargets}
                        period={period}
                        segment={currentSegment}
                    />
                    <EditReportForm
                        currentSegment={currentSegment}
                        reportData={reportData}
                        period={period}
                    />
                    <CollapsibleCard
                        title="Proses Order Complete"
                        isExpanded={isCompleteSectionExpanded}
                        onToggle={() =>
                            setIsCompleteSectionExpanded(
                                !isCompleteSectionExpanded,
                            )
                        }
                    >
                        <div className="bg-gray-50 p-4 rounded-md">
                            <h3 className="font-semibold text-lg text-gray-800">
                                Unggah & Sinkronisasi Order Complete
                            </h3>
                            <p className="text-gray-500 mt-1 text-sm">
                                Unggah file excel untuk langsung mengubah status
                                order 'in progress' menjadi 'complete'.
                            </p>
                            <form
                                onSubmit={submitCompleteFile}
                                className="mt-4 space-y-4"
                            >
                                <div>
                                    <input
                                        type="file"
                                        name="complete_document"
                                        className="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100"
                                        onChange={(e) =>
                                            setCompleteDataForm(
                                                "complete_document",
                                                e.target.files[0],
                                            )
                                        }
                                        disabled={completeProcessing}
                                    />
                                    {completeErrors.complete_document && (
                                        <p className="text-red-500 text-xs mt-1">
                                            {completeErrors.complete_document}
                                        </p>
                                    )}
                                </div>
                                <PrimaryButton
                                    type="submit"
                                    className="bg-green-600 hover:bg-green-700 focus:bg-green-700 active:bg-green-800"
                                    disabled={completeProcessing}
                                >
                                    {completeProcessing
                                        ? "Memproses..."
                                        : "Proses File Complete"}
                                </PrimaryButton>
                            </form>
                        </div>
                    </CollapsibleCard>
                    <CollapsibleCard
                        title="Proses Order Cancel"
                        isExpanded={isCancelSectionExpanded}
                        onToggle={() =>
                            setIsCancelSectionExpanded(!isCancelSectionExpanded)
                        }
                    >
                        <div className="bg-gray-50 p-4 rounded-md">
                            <h3 className="font-semibold text-lg text-gray-800">
                                Unggah Order Cancel
                            </h3>
                            <p className="text-gray-500 mt-1 text-sm">
                                Unggah file excel berisi order yang akan
                                di-cancel.
                            </p>
                            <form
                                onSubmit={submitCancelFile}
                                className="mt-4 space-y-4"
                            >
                                <div>
                                    <input
                                        type="file"
                                        name="cancel_document"
                                        className="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-red-50 file:text-red-700 hover:file:bg-red-100"
                                        onChange={(e) =>
                                            setCancelDataForm(
                                                "cancel_document",
                                                e.target.files[0],
                                            )
                                        }
                                        disabled={cancelProcessing}
                                    />
                                    {cancelErrors.cancel_document && (
                                        <p className="text-red-500 text-xs mt-1">
                                            {cancelErrors.cancel_document}
                                        </p>
                                    )}
                                </div>
                                {progressStates.cancel !== null && (
                                    <ProgressBar
                                        progress={progressStates.cancel}
                                        text="Memproses file..."
                                    />
                                )}
                                <PrimaryButton
                                    type="submit"
                                    className="bg-red-600 hover:bg-red-700 focus:bg-red-700 active:bg-red-800"
                                    disabled={cancelProcessing}
                                >
                                    {cancelProcessing
                                        ? "Memproses..."
                                        : "Proses File Cancel"}
                                </PrimaryButton>
                            </form>
                        </div>
                    </CollapsibleCard>
                </div>
            </div>
            <AgentFormModal
                isOpen={isModalOpen}
                onClose={closeModal}
                agent={editingAgent}
            />
        </AuthenticatedLayout>
    );
}
