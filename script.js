document.addEventListener("DOMContentLoaded", () => {
  const yearEl = document.getElementById("year");
  if (yearEl) yearEl.textContent = new Date().getFullYear();

  const menuToggle = document.getElementById("menuToggle");
  const mainNav = document.getElementById("mainNav");
  if (menuToggle && mainNav) {
    menuToggle.addEventListener("click", () => {
      const isOpen = mainNav.classList.toggle("is-open");
      menuToggle.setAttribute("aria-expanded", String(isOpen));
    });
    mainNav.querySelectorAll("a").forEach((link) => {
      link.addEventListener("click", () => {
        mainNav.classList.remove("is-open");
        menuToggle.setAttribute("aria-expanded", "false");
      });
    });
  }

  document.querySelectorAll(".faq-question").forEach((btn) => {
    btn.addEventListener("click", () => {
      const expanded = btn.getAttribute("aria-expanded") === "true";
      const answer = btn.nextElementSibling;
      document.querySelectorAll(".faq-question").forEach((other) => {
        if (other !== btn) {
          other.setAttribute("aria-expanded", "false");
          other.nextElementSibling.style.maxHeight = null;
        }
      });
      btn.setAttribute("aria-expanded", String(!expanded));
      answer.style.maxHeight = expanded ? null : answer.scrollHeight + "px";
    });
  });

  const form = document.getElementById("contactForm");
  const submitBtn = document.getElementById("submitBtn");
  const statusEl = document.getElementById("formStatus");

  if (form) {
    form.addEventListener("submit", async (e) => {
      e.preventDefault();
      statusEl.textContent = "";
      statusEl.className = "form-note";

      const honeypot = form.querySelector("#website");
      if (honeypot && honeypot.value.trim() !== "") {
        statusEl.textContent = "Mulțumim! Cererea a fost trimisă.";
        statusEl.classList.add("is-success");
        form.reset();
        return;
      }

      const nume = form.nume.value.trim();
      const telefon = form.telefon.value.trim();

      if (!nume || !telefon) {
        statusEl.textContent = "Completează numele și telefonul, te rugăm.";
        statusEl.classList.add("is-error");
        return;
      }

      const telefonValid = /^[0-9+\s()-]{6,20}$/.test(telefon);
      if (!telefonValid) {
        statusEl.textContent = "Verifică numărul de telefon introdus.";
        statusEl.classList.add("is-error");
        return;
      }

      const payload = {
        nume,
        telefon,
        localitate: form.localitate.value,
        serviciu: form.serviciu.value,
        mesaj: form.mesaj.value.trim(),
      };

      submitBtn.disabled = true;
      submitBtn.textContent = "Se trimite...";

      try {
        const res = await fetch("trimite.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify(payload),
        });
        const data = await res.json().catch(() => ({}));
        if (res.ok && data.ok) {
          statusEl.textContent = "Mulțumim! Cererea a fost trimisă, te contactăm în curând.";
          statusEl.classList.add("is-success");
          form.reset();
        } else {
          throw new Error(data.error || "Eroare la trimitere");
        }
      } catch (err) {
        statusEl.textContent = "Nu am putut trimite formularul. Sună-ne direct la 060 000 000.";
        statusEl.classList.add("is-error");
      } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = "Trimite cererea";
      }
    });
  }
});
