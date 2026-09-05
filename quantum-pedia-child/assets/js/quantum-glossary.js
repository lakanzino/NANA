(() => {
console.log('GLOSSARY JS LOADED');
    const tooltip = document.createElement('div');

    tooltip.className = 'quantum-glossary-tooltip';

    tooltip.setAttribute(
        'role',
        'tooltip'
    );

    document.body.appendChild(
        tooltip
    );

    const hide = () => {
        tooltip.classList.remove('active');
    };

    const show = term => {

        tooltip.textContent =
            term.dataset.definition || '';

        tooltip.classList.add(
            'active'
        );

        const rect =
            term.getBoundingClientRect();

        requestAnimationFrame(() => {

            const width =
                tooltip.offsetWidth;

            const height =
                tooltip.offsetHeight;

            let left =
                rect.left +
                rect.width / 2 -
                width / 2;

            left = Math.max(
                12,
                Math.min(
                    left,
                    window.innerWidth -
                    width -
                    12
                )
            );

            let top =
                rect.top -
                height -
                14;

            if ( top < 10 ) {
                top =
                    rect.bottom + 14;
            }

            tooltip.style.left =
                `${left}px`;

            tooltip.style.top =
                `${top}px`;
        });
    };

    document.addEventListener(
        'mouseover',
        e => {

            const term =
                e.target.closest(
                    '.quantum-glossary-term'
                );

            if ( ! term ) {
                return;
            }

            show(term);
        }
    );

    document.addEventListener(
        'mouseout',
        e => {

            if (
                e.target.closest(
                    '.quantum-glossary-term'
                )
            ) {
                hide();
            }
        }
    );

    document.addEventListener(
        'click',
        e => {

            const term =
                e.target.closest(
                    '.quantum-glossary-term'
                );

            if ( ! term ) {
                hide();
                return;
            }

            show(term);
        }
    );

    window.addEventListener(
        'scroll',
        hide,
        { passive:true }
    );

    window.addEventListener(
        'resize',
        hide,
        { passive:true }
    );

})();