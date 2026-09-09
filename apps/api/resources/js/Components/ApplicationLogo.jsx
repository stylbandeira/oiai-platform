export default function ApplicationLogo({ height = 16 }) {
    const heightClass = {
        10: 'h-10',
        12: 'h-12',
        16: 'h-16',
        20: 'h-20',
        24: 'h-24',
        28: 'h-28',
        32: 'h-32',
        64: 'h-64',
    };

    const className = `object-contain ${heightClass[height] || 'h-10'}`;

    return (
        <a href="/" rel="noopener noreferrer">
            <img className={className} src="/storage/img/primary-logo.png" alt="Logo" />
        </a>
    );
}
