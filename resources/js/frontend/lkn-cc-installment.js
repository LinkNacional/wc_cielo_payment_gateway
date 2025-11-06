/* eslint-disable no-undef */
(function ($) {
  'use strict'

  $(window).on('load', () => {
    lknWCCieloLoadInstallments()
    $('body').on('updated_checkout', lknWCCieloLoadInstallments)
    lknWCCieloInitInstallmentEvents()
  })

  lknWCCieloLoadInstallments()
  $('body').on('updated_checkout', function () {
    lknWCCieloLoadInstallments()
    lknWCCieloInitInstallmentEvents()
  })

  // Inicializar eventos de change para parcelas
  function lknWCCieloInitInstallmentEvents() {
    $(document).on('change', '#lkn_cc_installments', function () {
      const installment = $(this).val()
      const paymentMethod = 'lkn_cielo_credit'

      if (installment && typeof lknWCCieloCreditAjax !== 'undefined') {
        lknWCCieloUpdateInstallmentSession(paymentMethod, installment)
      }
    })
  }

  // Função para atualizar a sessão com a parcela selecionada
  function lknWCCieloUpdateInstallmentSession(paymentMethod, installment) {
    if (typeof lknWCCieloCreditAjax === 'undefined') {
      return
    }

    // Verificar se a parcela já é a mesma da sessão (evitar trigger desnecessário)
    if (lknWCCieloCreditAjax.current_installment === installment) {
      return
    }

    $.ajax({
      url: lknWCCieloCreditAjax.ajaxurl,
      type: 'POST',
      data: {
        action: 'lkn_update_payment_fees',
        nonce: lknWCCieloCreditAjax.nonce,
        payment_method: paymentMethod,
        installment: installment
      },
      success: function (response) {
        if (response.success) {
          // Atualizar a parcela atual em memória
          lknWCCieloCreditAjax.current_installment = installment
          // Trigger para atualizar o checkout após definir a parcela
          $('body').trigger('update_checkout')
        } else {
          console.error('Erro ao atualizar parcela:', response.data.message)
        }
      },
      error: function (xhr, status, error) {
        console.error('Erro na requisição AJAX:', error)
      }
    })
  }

  function lknWCCieloLoadInstallments() {
    const lknInstallmentSelect = document.getElementById('lkn_cc_installments')
    const lknTotal = document.getElementById('lkn_cc_installment_total') // produto + frete
    const lknFeesTotal = document.getElementById('lkn_cc_fees_total') // fees
    const lknTaxesTotal = document.getElementById('lkn_cc_taxes_total') // taxes
    const lknDiscountsTotal = document.getElementById('lkn_cc_discounts_total') // discounts
    let lknInstallmentLimit = document.getElementById('lkn_cc_installment_limit')
    let lknInstallmentInterest = document.getElementById('lkn_cc_installment_interest')
    let lknInstallmentMin = document.getElementById('lkn_cc_installment_min')

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
      console.log(subtotalShipping)
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

        const formatedInstallment = new Intl.NumberFormat('pt-br', { style: 'currency', currency: lknWCCieloCredit.currency }).format(finalInstallment)
        const option = document.createElement('option')

        // Texto dinâmico baseado na configuração
        let defaultText = ' sem juros' // padrão
        if (typeof lknWCCieloCreditConfig !== 'undefined' && lknWCCieloCreditConfig.interest_or_discount === 'discount') {
          defaultText = ' sem desconto'
        }

        console.log(defaultText)

        let text = document.createTextNode(i + 'x de ' + formatedInstallment + defaultText)

        if (typeof lknWCCieloCredit !== 'undefined' && lknWCCieloCredit.licenseResult) {
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
                const formatedInterest = new Intl.NumberFormat('pt-br', { style: 'currency', currency: lknWCCieloCredit.currency }).format(interestInstallment)

                text = document.createTextNode(i + 'x de ' + formatedInterest + ' (' + installmentObj.interest + '% de juros)')
              } else if (installmentObj.discount) {
                // Calcular desconto apenas sobre subtotal + frete, depois somar fees, subtrair descontos e somar taxes
                const discountAmount = subtotalShipping - (subtotalShipping * (installmentObj.discount / 100))
                const discountInstallment = (discountAmount / i) + feesTotal - discountsTotal + taxesTotal
                const formatedDiscount = new Intl.NumberFormat('pt-br', { style: 'currency', currency: lknWCCieloCredit.currency }).format(discountInstallment)

                text = document.createTextNode(i + 'x de ' + formatedDiscount + ' (' + installmentObj.discount + '% de desconto)')
              }
              break // Sair do loop quando encontrar a configuração
            }
          }
        } else {
          // Se a licença NÃO está ativa, remove o texto "sem juros"/"sem desconto"
          text = document.createTextNode(i + 'x de ' + formatedInstallment)
        }

        console.log(formatedInstallment)

        option.value = i
        option.appendChild(text)
        lknInstallmentSelect.appendChild(option)

        // Verificar se é a parcela atual da sessão e selecionar
        if (typeof lknWCCieloCreditAjax !== 'undefined' &&
          lknWCCieloCreditAjax.current_installment &&
          i === parseInt(lknWCCieloCreditAjax.current_installment)) {
          option.selected = true
        }

        if ((subtotalShipping / (i + 1)) < lknInstallmentMin) {
          break
        }
      }
    }
  }
})(jQuery)
