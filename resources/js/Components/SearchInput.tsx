import React from 'react';
import { MagnifyingGlassIcon } from '@heroicons/react/24/outline';
import { router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

interface SearchInputProps {
    value?: string;
    placeholder?: string;
    className?: string;
    onChange?: (value: string) => void;
    onSearch?: (value: string) => void;
    paramName?: string;
}

export default function SearchInput({
    value: initialValue = '',
    placeholder = 'Search…',
    className = '',
    onChange,
    paramName = 'search',
}: SearchInputProps) {
    const [value, setValue] = useState(initialValue);
    const debounceRef = useRef<ReturnType<typeof setTimeout>>();

    useEffect(() => {
        setValue(initialValue);
    }, [initialValue]);

    const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const v = e.target.value;
        setValue(v);
        if (onChange) {
            onChange(v);
        } else {
            clearTimeout(debounceRef.current);
            debounceRef.current = setTimeout(() => {
                router.get(window.location.pathname, { [paramName]: v }, {
                    preserveState: true,
                    replace: true,
                });
            }, 350);
        }
    };

    return (
        <div className={`relative ${className}`}>
            <MagnifyingGlassIcon className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" />
            <input
                type="search"
                value={value}
                onChange={handleChange}
                placeholder={placeholder}
                className="form-input pl-9 !py-2"
            />
        </div>
    );
}
