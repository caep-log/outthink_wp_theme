document.addEventListener("DOMContentLoaded", () => {
    const sections = document.querySelectorAll("main section");
    const navLinks = document.querySelectorAll(".outthink-header-menu a");

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const id = entry.target.getAttribute("id");

                    navLinks.forEach(link => {
                        link.classList.remove("active");
                        if (link.getAttribute("href") === `#${id}`) {
                            link.classList.add("active");
                        }
                    });
                }
            });
        },
        {
            root: null,
            threshold: 0.6 // ajusta qué tan visible debe estar
        }
    );

    sections.forEach(section => observer.observe(section));
});