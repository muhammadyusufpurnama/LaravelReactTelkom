// resources/js/Components/SmeReportTable.jsx

import React, { useMemo } from 'react';
import {
    DndContext,
    closestCenter,
    KeyboardSensor,
    PointerSensor,
    useSensor,
    useSensors,
} from "@dnd-kit/core";
import {
    arrayMove,
    SortableContext,
    sortableKeyboardCoordinates,
    horizontalListSortingStrategy,
    useSortable,
} from "@dnd-kit/sortable";
import { CSS } from "@dnd-kit/utilities";

const formatPercent = (value) => {
    const num = Number(value);
    if (!isFinite(num) || num === 0) return "0.0%";
    return `${num.toFixed(1)}%`;
};
const formatRupiah = (value, decimals = 2) =>
    (Number(value) || 0).toFixed(decimals);
const formatNumber = (value) => Number(value) || 0;
const formatDate = (dateString) => {
    if (!dateString) return "-";
    return new Date(dateString).toLocaleString("id-ID", {
        year: "numeric",
        month: "2-digit",
        day: "2-digit",
        hour: "2-digit",
        minute: "2-digit",
    });
};

const SmeReportTable = ({
    data = [],
    decimalPlaces,
    tableConfig,
    setTableConfig,
}) => {
    const sensors = useSensors(
        useSensor(PointerSensor),
        useSensor(KeyboardSensor, {
            coordinateGetter: sortableKeyboardCoordinates,
        }),
    );

    const findColumnDefinition = (keyToFind) => {
        for (const group of tableConfig) {
            for (const col of group.columns) {
                if (col.key === keyToFind) return { colDef: col, parentColDef: null };
                if (col.subColumns) {
                    for (const subCol of col.subColumns) {
                        if (col.key + subCol.key === keyToFind) {
                            return { colDef: subCol, parentColDef: col };
                        }
                    }
                }
            }
        }
        return { colDef: null, parentColDef: null };
    };

    const getCellValue = (item, columnDef, parentColumnDef = null) => {
        const fullKey = parentColumnDef ? parentColumnDef.key + columnDef.key : columnDef.key;

        if (columnDef.type === "calculation") {
            const { operation, operands } = columnDef.calculation;
            const values = operands.map((opKey) => {
                const { colDef: opDef, parentColDef: opParentDef } = findColumnDefinition(opKey);
                if (!opDef) return 0;
                return opDef.type === "calculation"
                    ? getCellValue(item, opDef, opParentDef)
                    : Number(item[opKey] || 0); // Convert to number for calculation
            });

            switch (operation) {
                case "percentage":
                    const [numerator, denominator] = values;
                    if (!denominator || denominator === 0) return formatPercent(0);
                    return formatPercent((numerator / denominator) * 100);
                case "sum":
                    return formatNumber(values.reduce((a, b) => a + b, 0));
                case "average":
                    if (values.length === 0) return 0;
                    return formatNumber(values.reduce((a, b) => a + b, 0) / values.length);
                case "count":
                    return values.filter((v) => v !== 0).length;
                default:
                    return "N/A";
            }
        }

        if (fullKey.startsWith("revenue_")) {
            return formatRupiah(item[fullKey], decimalPlaces);
        }

        // Cek jika key ada di item, jika tidak return 0
        const value = item.hasOwnProperty(fullKey) ? item[fullKey] : 0;
        return formatNumber(value);
    };

    const totals = useMemo(() => {
        if (!data || data.length === 0) return {};
        const initialTotals = {};
        tableConfig.forEach((group) => {
            group.columns.forEach((col) => {
                const processCol = (c, parentC = null) => {
                    const key = parentC ? parentC.key + c.key : c.key;
                    if (c.subColumns) {
                        c.subColumns.forEach(sc => processCol(sc, c));
                    } else if (c.type !== 'calculation') {
                        initialTotals[key] = data.reduce((sum, item) => sum + (Number(item[key]) || 0), 0);
                    }
                };
                processCol(col);
            });
        });
        return initialTotals;
    }, [data, tableConfig]);

    const handleDragEnd = (event) => {
        const { active, over } = event;
        if (!over || active.id === over.id) return;

        const activeType = active.data.current?.type;
        const overType = over.data.current?.type;

        if (activeType === "group" && overType === "group") {
            setTableConfig((config) => {
                const oldIndex = config.findIndex((g) => g.groupTitle === active.id);
                const newIndex = config.findIndex((g) => g.groupTitle === over.id);
                return arrayMove(config, oldIndex, newIndex);
            });
        } else if (activeType === "column" && overType === "column" && active.data.current?.parentGroupTitle === over.data.current?.parentGroupTitle) {
            const parentGroupTitle = active.data.current.parentGroupTitle;
            setTableConfig((config) => {
                const newConfig = JSON.parse(JSON.stringify(config));
                const group = newConfig.find(g => g.groupTitle === parentGroupTitle);
                if (!group) return config;
                const oldIndex = group.columns.findIndex(c => c.key === active.id.split('.').pop());
                const newIndex = group.columns.findIndex(c => c.key === over.id.split('.').pop());
                if (oldIndex !== -1 && newIndex !== -1) {
                    group.columns = arrayMove(group.columns, oldIndex, newIndex);
                }
                return newConfig;
            });
        } else if (activeType === "sub-column" && overType === "sub-column" && active.data.current?.parentColumnKey === over.data.current?.parentColumnKey) {
            const { parentGroupTitle, parentColumnKey } = active.data.current;
             setTableConfig((config) => {
                const newConfig = JSON.parse(JSON.stringify(config));
                const group = newConfig.find(g => g.groupTitle === parentGroupTitle);
                const parentCol = group?.columns.find(c => c.key === parentColumnKey);
                if (!parentCol?.subColumns) return config;
                const oldIndex = parentCol.subColumns.findIndex(sc => sc.key === active.id.split('.').pop());
                const newIndex = parentCol.subColumns.findIndex(sc => sc.key === over.id.split('.').pop());
                if (oldIndex !== -1 && newIndex !== -1) {
                    parentCol.subColumns = arrayMove(parentCol.subColumns, oldIndex, newIndex);
                }
                return newConfig;
            });
        }
    };

    const DraggableHeaderCell = ({ group }) => {
        const { attributes, listeners, setNodeRef, transform, transition } = useSortable({ id: group.groupTitle, data: { type: "group" } });
        const style = { transform: CSS.Transform.toString(transform), transition };
        const colSpan = group.columns.reduce((sum, col) => sum + (col.subColumns?.length || 1), 0);
        return <th ref={setNodeRef} style={style} {...attributes} {...listeners} className={`border p-2 ${group.groupClass} cursor-grab`} colSpan={colSpan}>{group.groupTitle}</th>;
    };

    const DraggableColumnHeader = ({ group, col }) => {
        const uniqueId = `${group.groupTitle}.${col.key}`;
        const { attributes, listeners, setNodeRef, transform, transition } = useSortable({ id: uniqueId, data: { type: "column", parentGroupTitle: group.groupTitle } });
        const style = { transform: CSS.Transform.toString(transform), transition };
        return <th ref={setNodeRef} style={style} {...attributes} {...listeners} className={`border p-2 ${group.columnClass || "bg-gray-700"} cursor-grab`} colSpan={col.subColumns?.length || 1} rowSpan={col.subColumns ? 1 : 2}>{col.title}</th>;
    };

    const DraggableSubColumnHeader = ({ group, col, subCol }) => {
        const uniqueId = `${group.groupTitle}.${col.key}.${subCol.key}`;
        const { attributes, listeners, setNodeRef, transform, transition } = useSortable({ id: uniqueId, data: { type: "sub-column", parentGroupTitle: group.groupTitle, parentColumnKey: col.key } });
        const style = { transform: CSS.Transform.toString(transform), transition };
        return <th ref={setNodeRef} style={style} {...attributes} {...listeners} className={`border p-1 ${group.subColumnClass || "bg-gray-600"} cursor-grab`}>{subCol.title}</th>;
    };

    return (
        <DndContext sensors={sensors} collisionDetection={closestCenter} onDragEnd={handleDragEnd}>
            <div className="overflow-x-auto text-xs">
                <table className="w-full border-collapse text-center">
                    <thead className="bg-gray-800 text-white">
                        <tr>
                            <th className="border p-2 align-middle bg-gray-800" rowSpan={3}>WILAYAH TELKOM</th>
                            <SortableContext items={tableConfig.map(g => g.groupTitle)} strategy={horizontalListSortingStrategy}>
                                {tableConfig.map(group => <DraggableHeaderCell key={group.groupTitle} group={group} />)}
                            </SortableContext>
                        </tr>
                        <tr className="font-semibold">
                            {tableConfig.map(group => (
                                <SortableContext key={`${group.groupTitle}-cols`} items={group.columns.map(c => `${group.groupTitle}.${c.key}`)} strategy={horizontalListSortingStrategy}>
                                    {group.columns.map(col => <DraggableColumnHeader key={col.key} group={group} col={col} />)}
                                </SortableContext>
                            ))}
                        </tr>
                        <tr className="font-medium">
                            {tableConfig.map(group => group.columns.map(col => col.subColumns ? (
                                <SortableContext key={`${group.groupTitle}-${col.key}-subcols`} items={col.subColumns.map(sc => `${group.groupTitle}.${col.key}.${sc.key}`)} strategy={horizontalListSortingStrategy}>
                                    {col.subColumns.map(subCol => <DraggableSubColumnHeader key={subCol.key} group={group} col={col} subCol={subCol} />)}
                                </SortableContext>
                            ) : null))}
                        </tr>
                    </thead>
                    <tbody>
                        {data.length > 0 ? data.map(item => (
                            <tr key={item.nama_witel} className="bg-white hover:bg-gray-50 text-black">
                                <td className="border p-2 font-semibold text-left">{item.nama_witel}</td>
                                {tableConfig.map(group => group.columns.map(col => col.subColumns ? (
                                    col.subColumns.map(subCol => <td key={`${item.nama_witel}-${col.key}-${subCol.key}`} className={`border p-2 ${subCol.cellClassName || ""}`}>{getCellValue(item, subCol, col)}</td>)
                                ) : (
                                    <td key={`${item.nama_witel}-${col.key}`} className={`border p-2 ${col.cellClassName || ""}`}>{getCellValue(item, col)}</td>
                                )))}
                            </tr>
                        )) : (
                            <tr><td colSpan={100} className="text-center p-4 border text-gray-500">Tidak ada data.</td></tr>
                        )}
                        <tr className="font-bold text-white">
                            <td className="border p-2 text-left bg-gray-800">GRAND TOTAL</td>
                            {tableConfig.map(group => group.columns.map(col => col.subColumns ? (
                                col.subColumns.map(subCol => <td key={`total-${col.key}-${subCol.key}`} className={`border p-2 ${group.groupClass}`}>{getCellValue(totals, subCol, col)}</td>)
                            ) : (
                                <td key={`total-${col.key}`} className={`border p-2 ${group.groupClass}`}>{getCellValue(totals, col)}</td>
                            )))}
                        </tr>
                    </tbody>
                </table>
            </div>
        </DndContext>
    );
};

export default SmeReportTable;
