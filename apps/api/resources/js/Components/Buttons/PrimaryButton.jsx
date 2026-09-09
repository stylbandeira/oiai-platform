import React from 'react';
import PropTypes from 'prop-types';

export default function PrimaryButton({
    children,
    onClick,
    type = 'button',
    variant = 'default',
    className = '',
    ...props
}) {
    const baseStyles = 'px-4 py-2 rounded font-semibold text-sm transition duration-150 ease-in-out';

    const variants = {
        default: 'bt-primary',
        clean: 'bt-secondary',
        disabled: 'bg-gray-300 text-gray-500 cursor-not-allowed pointer-events-none',
    };

    return (
        <button
            type={type}
            className={`${baseStyles} ${variants[variant]} ${className}`}
            onClick={variant !== 'disabled' ? onClick : undefined}
            disabled={variant === 'disabled'}
            {...props}
        >
            {children}
        </button>
    );
}

PrimaryButton.propTypes = {
    children: PropTypes.node.isRequired,
    onClick: PropTypes.func,
    type: PropTypes.oneOf(['button', 'submit', 'reset']),
    variant: PropTypes.oneOf(['default', 'clean', 'disabled']),
    className: PropTypes.string,
};
