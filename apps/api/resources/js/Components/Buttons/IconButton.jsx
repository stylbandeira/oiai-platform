import React from 'react';
import { Plus, Minus } from 'lucide-react';

const IconButton = ({ variant = 'add', onClick }) => {

    const variants = {
        add: 'bg-blue-500 text-white w-10 h-10 rounded-2xl flex items-center justify-center',
        remove: 'bg-red-500 text-white w-10 h-10 rounded-2xl flex items-center justify-center',
        disabled: 'bg-gray-400 text-gray-600 cursor-not-allowed w-10 h-10 rounded-2xl flex items-center justify-center',
    };

    const iconSize = 28;

    const renderIcon = () => {
        switch (variant) {
            case 'add':
                return <Plus size={iconSize} strokeWidth={2.5} />;
            case 'remove':
                return <Minus size={iconSize} strokeWidth={2.5} />;
            case 'disabled':
                return <Plus size={iconSize} strokeWidth={2.5} />;
            default:
                return null;
        }
    };

    return (
        <button
            className={variants[variant]}
            onClick={onClick}
            disabled={variant === 'disabled'}
        >
            {renderIcon()}
        </button>
    );
};

export default IconButton;
