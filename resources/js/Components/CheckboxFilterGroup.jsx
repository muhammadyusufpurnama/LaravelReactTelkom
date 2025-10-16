// resources/js/Components/CheckboxFilterGroup.jsx

import React from 'react';

export default function CheckboxFilterGroup({ title, options, selectedOptions, onSelectionChange }) {
    const handleCheckboxChange = (option) => {
        const newSelection = selectedOptions.includes(option)
            ? selectedOptions.filter(item => item !== option)
            : [...selectedOptions, option];
        onSelectionChange(newSelection);
    };

    return (
        <div className="mt-4 border-t pt-3">
            <h5 className="text-sm font-semibold text-gray-600 mb-2">{title}</h5>
            <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2">
                {options.map(option => (
                    <label key={option} className="flex items-center space-x-2 text-xs text-gray-700">
                        <input
                            type="checkbox"
                            className="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                            checked={selectedOptions.includes(option)}
                            onChange={() => handleCheckboxChange(option)}
                        />
                        <span>{option}</span>
                    </label>
                ))}
            </div>
        </div>
    );
}
