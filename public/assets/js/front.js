/**
 * front.js — Scripts partagés Front
 * Cohérent avec admin.js
 */

document.addEventListener("DOMContentLoaded", () => {
    // ══════════════════════════════════════
    // 1. NAVIGATION — effet au scroll
    // ══════════════════════════════════════
    const nav = document.querySelector(".site-nav");

    if (nav) {
        const onScroll = () =>
            nav.classList.toggle("scrolled", window.scrollY > 10);
        window.addEventListener("scroll", onScroll, { passive: true });
        onScroll();
    }

    // ══════════════════════════════════════
    // 2. NAVIGATION — burger mobile
    // ══════════════════════════════════════
    const burger = document.querySelector(".nav-burger");
    const mobileMenu = document.querySelector(".nav-mobile");

    if (burger && mobileMenu) {
        burger.addEventListener("click", () => {
            const isOpen = burger.classList.toggle("open");
            mobileMenu.classList.toggle("open", isOpen);
            document.body.style.overflow = isOpen ? "hidden" : "";
        });

        mobileMenu.querySelectorAll("a").forEach((link) => {
            link.addEventListener("click", () => {
                burger.classList.remove("open");
                mobileMenu.classList.remove("open");
                document.body.style.overflow = "";
            });
        });
    }

    // ══════════════════════════════════════
    // 3. NAVIGATION — dropdown
    // ══════════════════════════════════════
    document.querySelectorAll(".nav-dropdown").forEach((dropdown) => {
        const toggle = dropdown.querySelector(".nav-dropdown-toggle");
        if (toggle) {
            toggle.addEventListener("click", (e) => {
                e.stopPropagation();
                const isOpen = dropdown.classList.toggle("open");
                document.querySelectorAll(".nav-dropdown").forEach((other) => {
                    if (other !== dropdown) other.classList.remove("open");
                });
            });
        }
    });

    document.addEventListener("click", () => {
        document
            .querySelectorAll(".nav-dropdown")
            .forEach((d) => d.classList.remove("open"));
    });

    // ══════════════════════════════════════
    // 4. FLASH MESSAGES — fermeture & auto-dismiss
    // ══════════════════════════════════════
    document.querySelectorAll(".flash").forEach((flash) => {
        const closeBtn = flash.querySelector(".flash-close");
        if (closeBtn)
            closeBtn.addEventListener("click", () => dismissFlash(flash));
        setTimeout(() => dismissFlash(flash), 5000);
    });

    function dismissFlash(el) {
        if (!el || !el.parentNode) return;
        el.style.transition = "opacity 0.25s ease, transform 0.25s ease";
        el.style.opacity = "0";
        el.style.transform = "translateX(14px)";
        setTimeout(() => el.remove(), 280);
    }

    // ══════════════════════════════════════
    // 5. ANIMATIONS — révélation au scroll
    // ══════════════════════════════════════
    const revealEls = document.querySelectorAll(".reveal");

    if (revealEls.length > 0 && "IntersectionObserver" in window) {
        const observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add("visible");
                        observer.unobserve(entry.target);
                    }
                });
            },
            { threshold: 0.1, rootMargin: "0px 0px -30px 0px" },
        );
        revealEls.forEach((el) => observer.observe(el));
    }

    // ══════════════════════════════════════
    // 6. SMOOTH SCROLL — ancres internes
    // ══════════════════════════════════════
    document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
        anchor.addEventListener("click", (e) => {
            const id = anchor.getAttribute("href");
            if (id === "#") return;
            const target = document.querySelector(id);
            if (target) {
                e.preventDefault();
                const navH =
                    parseInt(
                        getComputedStyle(
                            document.documentElement,
                        ).getPropertyValue("--nav-h"),
                    ) || 68;
                const top =
                    target.getBoundingClientRect().top +
                    window.scrollY -
                    navH -
                    16;
                window.scrollTo({ top, behavior: "smooth" });
            }
        });
    });
});
