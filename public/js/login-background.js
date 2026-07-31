(() => {
    'use strict';

    const canvas = document.getElementById('loginPixelRain');
    if (!canvas) return;

    const panel = canvas.parentElement;
    const context = canvas.getContext('2d', { alpha: false });
    if (!panel || !context) return;

    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    const colors = [
        [95, 45, 165],
        [112, 64, 199],
        [129, 84, 218],
        [47, 128, 237],
        [184, 169, 255],
        [255, 255, 255]
    ];

    const pixelScale = 4;
    let drops = [];
    let animationFrame = null;
    let previousTime = 0;

    function createDrop(randomPosition = false) {
        return {
            x: Math.floor(Math.random() * canvas.width),
            y: randomPosition
                ? Math.random() * canvas.height
                : -Math.random() * canvas.height * 0.45,
            speed: 0.22 + Math.random() * 0.62,
            length: 5 + Math.floor(Math.random() * 17),
            width: Math.random() > 0.88 ? 2 : 1,
            opacity: 0.1 + Math.random() * 0.42,
            delay: Math.random() * 130,
            color: colors[Math.floor(Math.random() * colors.length)]
        };
    }

    function resetDrop(drop) {
        Object.assign(drop, createDrop(false));
    }

    function paintBackground() {
        context.fillStyle = 'rgb(11, 19, 34)';
        context.fillRect(0, 0, canvas.width, canvas.height);

        for (let index = 0; index < 24; index += 1) {
            const x = (index * 37) % canvas.width;
            const y = (index * 61) % canvas.height;
            context.fillStyle = 'rgba(112, 64, 199, 0.035)';
            context.fillRect(x, y, 2, 2);
        }
    }

    function draw(time = 0, singleFrame = false) {
        const delta = previousTime
            ? Math.min(2.4, (time - previousTime) / 16.67)
            : 1;

        previousTime = time;
        paintBackground();

        for (const drop of drops) {
            if (drop.delay > 0) {
                drop.delay -= delta;
                continue;
            }

            const [red, green, blue] = drop.color;
            for (let segment = 0; segment < drop.length; segment += 1) {
                const fade = Math.pow(1 - segment / drop.length, 1.7);
                const alpha = drop.opacity * fade;
                const y = Math.floor(drop.y - segment * 1.15);
                if (y < 0 || y >= canvas.height) continue;

                context.fillStyle = `rgba(${red}, ${green}, ${blue}, ${alpha})`;
                context.fillRect(drop.x, y, drop.width, 1);
            }

            context.fillStyle = `rgba(${red}, ${green}, ${blue}, ${Math.min(0.85, drop.opacity + 0.18)})`;
            context.fillRect(drop.x, Math.floor(drop.y), Math.max(1, drop.width), 1);

            if (!singleFrame) {
                drop.y += drop.speed * delta;
                if (drop.y - drop.length > canvas.height) resetDrop(drop);
            }
        }

        if (!singleFrame && !reduceMotion.matches && !document.hidden) {
            animationFrame = window.requestAnimationFrame(draw);
        }
    }

    function resizeCanvas() {
        const bounds = panel.getBoundingClientRect();
        canvas.width = Math.max(120, Math.floor(bounds.width / pixelScale));
        canvas.height = Math.max(120, Math.floor(bounds.height / pixelScale));
        context.imageSmoothingEnabled = false;

        const quantity = Math.max(34, Math.floor(canvas.width / 3.8));
        drops = Array.from({ length: quantity }, () => createDrop(true));
        draw(0, true);
    }

    function startAnimation() {
        if (animationFrame) window.cancelAnimationFrame(animationFrame);
        animationFrame = null;
        previousTime = 0;

        if (reduceMotion.matches) {
            draw(0, true);
        } else {
            animationFrame = window.requestAnimationFrame(draw);
        }
    }

    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            if (animationFrame) window.cancelAnimationFrame(animationFrame);
            animationFrame = null;
        } else {
            startAnimation();
        }
    });

    if (typeof reduceMotion.addEventListener === 'function') {
        reduceMotion.addEventListener('change', startAnimation);
    }

    if (typeof ResizeObserver === 'function') {
        const resizeObserver = new ResizeObserver(() => {
            resizeCanvas();
            startAnimation();
        });
        resizeObserver.observe(panel);
    } else {
        window.addEventListener('resize', () => {
            resizeCanvas();
            startAnimation();
        });
    }

    resizeCanvas();
    startAnimation();
})();
