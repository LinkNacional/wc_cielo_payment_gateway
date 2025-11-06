/* eslint-disable no-undef */
(function ($) {
  'use strict'

  $(window).on('load', () => {
    lknWCCieloLoadInstallments()
    $('body').on('updated_checkout', lknWCCieloLoadInstallments)
  })

  lknWCCieloLoadInstallments()
  $('body').on('updated_checkout', lknWCCieloLoadInstallments)

  function lknWCCieloShowInstallments() {
    const installmentShow = $('#lkn_cielo_3ds_installment_show')
    const installmentRow = $('#lkn-cc-dc-installment-row')

    if (installmentShow && installmentRow) {
      if (installmentShow.length && installmentShow.val() === 'no') {
        if (installmentRow.length) {
          installmentRow.show()
          installmentShow.val('yes')
        }
      } else {
        if (installmentRow.length) {
          installmentRow.hide()
          installmentShow.val('no')
        }
      }
    }
  }

  function lknWCCieloLoadInstallments() {
    const typeCard = $('#lkn_cc_type')
    const lknInstallmentSelect = document.getElementById('lkn_cc_dc_installments')
    const lknTotal = document.getElementById('lkn_cc_dc_installment_total') // produto + frete
    const lknFeesTotal = document.getElementById('lkn_cc_dc_fees_total') // fees
    const lknTaxesTotal = document.getElementById('lkn_cc_dc_taxes_total') // taxes
    const lknDiscountsTotal = document.getElementById('lkn_cc_dc_discounts_total') // discounts
    let lknInstallmentLimit = document.getElementById('lkn_cc_dc_installment_limit')
    let lknInstallmentInterest = document.getElementById('lkn_cc_dc_installment_interest')
    let lknInstallmentMin = document.getElementById('lkn_cc_dc_installment_min')

    if (typeCard.length) {
      typeCard.on('change', lknWCCieloShowInstallments)
      lknWCCieloShowInstallments()
    }

    if (lknInstallmentLimit) {
      lknInstallmentLimit = lknInstallmentLimit.value
    }

    if (lknInstallmentInterest) {
      lknInstallmentInterest = JSON.parse(lknInstallmentInterest.value)
    }

    if (lknInstallmentMin) {
      lknInstallmentMin = parseFloat(lknInstallmentMin.value)
    }

    // Remove installment options and repopulate installments
    if (lknInstallmentSelect && lknTotal) {
      // Separar os valores para cálculo correto
      const subtotalShipping = parseFloat(lknTotal.value) || 0 // produto + frete (para cálculo de juros)
      const feesTotal = lknFeesTotal ? parseFloat(lknFeesTotal.value) || 0 : 0 // fees (somado no final)
      const discountsTotal = lknDiscountsTotal ? parseFloat(lknDiscountsTotal.value) || 0 : 0 // discounts (subtraído antes dos taxes)
      const taxesTotal = lknTaxesTotal ? parseFloat(lknTaxesTotal.value) || 0 : 0 // taxes (somado no final)

      for (let c = 1; c < lknInstallmentSelect.childNodes.length; c + 2) {
        const childNode = lknInstallmentSelect.childNodes[c]
        lknInstallmentSelect.removeChild(childNode)
      }

      for (let i = 1; i <= lknInstallmentLimit; i++) {
        // Calcular parcela base: (subtotal + frete) / parcelas + fees externo - descontos + taxes
        let installmentBase = subtotalShipping / i
        // Valor final da parcela (fees somados, descontos subtraídos, taxes somados)
        let finalInstallment = installmentBase + feesTotal - discountsTotal + taxesTotal

        const formatedInstallment = new Intl.NumberFormat('pt-br', { style: 'currency', currency: 'BRL' }).format(finalInstallment)
        const option = document.createElement('option')

        // Texto dinâmico baseado na configuração
        let defaultText = ' sem juros' // padrão
        if (typeof lknWCCielo3dsConfig !== 'undefined' && lknWCCielo3dsConfig.interest_or_discount === 'discount') {
          defaultText = ' sem desconto'
        }

        let text = document.createTextNode(i + 'x de ' + formatedInstallment + defaultText)

        for (let t = 0; t < lknInstallmentInterest.length; t++) {
          const installmentObj = lknInstallmentInterest[t]
          // Verify if it is the right installment
          if (installmentObj.id === i) {
            if (installmentObj.label) {
              text = document.createTextNode(installmentObj.label)
            } else if (installmentObj.interest) {
              // Calcular juros apenas sobre subtotal + frete, depois somar fees, subtrair descontos e somar taxes
              const interestAmount = subtotalShipping + (subtotalShipping * (installmentObj.interest / 100))
              const interestInstallment = (interestAmount / i) + feesTotal - discountsTotal + taxesTotal
              const formatedInterest = new Intl.NumberFormat('pt-br', { style: 'currency', currency: 'BRL' }).format(interestInstallment)

              text = document.createTextNode(i + 'x de ' + formatedInterest + ' (' + installmentObj.interest + '% de juros)')
            } else if (installmentObj.discount) {
              // Calcular desconto apenas sobre subtotal + frete, depois somar fees, subtrair descontos e somar taxes
              const discountAmount = subtotalShipping - (subtotalShipping * (installmentObj.discount / 100))
              const discountInstallment = (discountAmount / i) + feesTotal - discountsTotal + taxesTotal
              const formatedDiscount = new Intl.NumberFormat('pt-br', { style: 'currency', currency: 'BRL' }).format(discountInstallment)

              text = document.createTextNode(i + 'x de ' + formatedDiscount + ' (' + installmentObj.discount + '% de desconto)')
            }
            break // Sair do loop quando encontrar a configuração
          }
        }

        // Se installment_discount NÃO está ativado, remove o texto "sem desconto"/"sem juros"  
        if (typeof lknWCCielo3dsConfig !== 'undefined' && lknWCCielo3dsConfig.installment_discount !== 'yes') {
          text = document.createTextNode(i + 'x de ' + formatedInstallment)
        }

        option.value = i
        option.appendChild(text)
        lknInstallmentSelect.appendChild(option)
        if ((subtotalShipping / (i + 1)) < lknInstallmentMin) {
          break
        }
      }
    }
  }
})(jQuery)
