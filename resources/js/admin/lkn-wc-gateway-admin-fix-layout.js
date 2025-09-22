(function ($) {
  $(window).load(function () {
    function agruparCampos(titulo, fieldIds) {
      const fieldsets = fieldIds
        .map((id, index) => {
          const el = document.getElementById(id);
          if (!el) return null;
          el.parentElement.style.justifyContent = "";
          el.parentElement.style.paddingTop = "20px";
          console.log(el.parentElement);

          if (index !== 0) {
            el.closest("tr").remove();
          }
          return el.closest("fieldset");
        })
        .filter(Boolean);

      if (fieldsets.length) {
        const targetTd = fieldsets[0].closest("td");
        const targetTh = targetTd.previousElementSibling;
        const label = targetTh.querySelector("label");
        const p = targetTh.querySelector("p");

        if (p) {
          if (p.innerHTML.includes("Google Pay.")) {
            //Remover o Google Pay do innerHTML e criar um <a> para https://pay.google.com/business/console/?hl=pt-br
            p.innerHTML = p.innerHTML.replace(/Google Pay./g, '');
            const a = document.createElement("a");
            a.href = "https://pay.google.com/business/console/?hl=pt-br";
            a.target = "_blank";
            a.rel = "noopener noreferrer";
            a.textContent = "Google Pay";
            p.appendChild(a);
          }
        }

        // Ajusta título e estilos
        if (label) label.innerHTML = titulo;
        targetTh.style.paddingTop = "50px";
        targetTh.style.verticalAlign = "";

        // Junta os outros fieldsets no mesmo <td>
        fieldsets.slice(1).forEach(fs => targetTd.appendChild(fs));
      }
    }

    // Grupo Cielo
    agruparCampos("Cielo", [
      "woocommerce_lkn_cielo_google_pay_env",
      "woocommerce_lkn_cielo_google_pay_merchant_id",
      "woocommerce_lkn_cielo_google_pay_merchant_key",
    ]);

    // Grupo Google
    agruparCampos("Google", [
      "woocommerce_lkn_cielo_google_pay_google_merchant_name",
      "woocommerce_lkn_cielo_google_pay_google_merchant_id",
      "woocommerce_lkn_cielo_google_pay_google_text_button",
    ]);
  });
})(jQuery);
