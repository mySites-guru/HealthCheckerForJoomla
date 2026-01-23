import "https://esm.sh/number-flow@0.5.4";

// Main counters (130 health checks)
const mainCounters = [
    { id: "hero-counter", threshold: 0.5 },
    { id: "checks-counter", threshold: 0.5 },
];

mainCounters.forEach(({ id, threshold }) => {
    const counter = document.getElementById(id);
    if (!counter) return;

    let hasAnimated = false;
    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting && !hasAnimated) {
                    hasAnimated = true;
                    counter.update(100);
                    setTimeout(() => counter.update(130), 100);
                    observer.disconnect();
                }
            });
        },
        { threshold },
    );
    observer.observe(counter);
});

// Hero screenshot counters (Good/Warning/Critical) - randomized, repeating
const statsCounterIds = [
    { id: "good-counter", delay: 0 },
    { id: "warning-counter", delay: 200 },
    { id: "critical-counter", delay: 400 },
];

// Track current values to animate from previous value
const currentValues = {
    "good-counter": 0,
    "warning-counter": 0,
    "critical-counter": 0,
};

function getRandomInt(min, max) {
    return Math.floor(Math.random() * (max - min + 1)) + min;
}

function animateStatsCounters() {
    statsCounterIds.forEach(({ id, delay }) => {
        const counter = document.getElementById(id);
        if (!counter) return;

        const start = currentValues[id];
        const end = getRandomInt(1, 130);
        currentValues[id] = end;

        setTimeout(() => {
            // Count up/down slowly through intermediate values
            const steps = 8;
            const increment = (end - start) / steps;
            let current = start;
            let step = 0;

            const interval = setInterval(() => {
                step++;
                if (step >= steps) {
                    counter.update(end);
                    clearInterval(interval);
                } else {
                    current += increment;
                    counter.update(Math.round(current));
                }
            }, 80);
        }, delay);
    });
}

// Find a common parent to observe (the screenshot container)
const goodCounter = document.getElementById("good-counter");
if (goodCounter) {
    let hasStarted = false;
    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting && !hasStarted) {
                    hasStarted = true;
                    observer.disconnect();

                    // Initial animation
                    animateStatsCounters();

                    // Repeat every 5 seconds
                    setInterval(animateStatsCounters, 5000);
                }
            });
        },
        { threshold: 0.5 },
    );
    observer.observe(goodCounter);
}
