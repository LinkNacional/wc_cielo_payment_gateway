(function ($) {
  $(window).load(function () {
    function agruparCampos(titulo, fieldIds) {
      const fieldsets = fieldIds
        .map((id, index) => {
          // Try different ID patterns to find the element
          let el = document.getElementById(id);
          if (!el && !id.endsWith('-control')) {
            el = document.getElementById(id + '-control');
          }
          
          // If still not found, try to find by name attribute or other methods
          if (!el) {
            // Look for radio buttons or elements with name attribute
            const radioButtons = document.querySelectorAll(`input[name="${id}"], input[name="${id}-control"]`);
            if (radioButtons.length > 0) {
              el = radioButtons[0];
            }
          }
          

          if (!el) return null;
          
          // Only apply styles if element has parentElement
          if (el.parentElement) {
            el.parentElement.style.justifyContent = "";
            el.parentElement.style.paddingTop = "20px";
          }
          
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
      "woocommerce_lkn_cielo_google_pay_require_3ds",
    ]);
  });
})(jQuery);
