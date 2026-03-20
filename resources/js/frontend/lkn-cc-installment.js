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
      const feesTotal = lknFeesTotal ? parseFloat(lknFeesTotal.value) || 0 : 0 // fees (somado no final)
      const discountsTotal = lknDiscountsTotal ? parseFloat(lknDiscountsTotal.value) || 0 : 0 // discounts (subtraído antes dos taxes)
      const taxesTotal = lknTaxesTotal ? parseFloat(lknTaxesTotal.value) || 0 : 0 // taxes (somado no final)

      // Se o limite de parcelas for 1 ou menor, ou se não houver limite, não renderiza o select
      if (!lknInstallmentLimit || lknInstallmentLimit <= 1) {
        const installmentRow = document.getElementById('lkn-cc-installment-row')
        if (installmentRow) {
          installmentRow.style.display = 'none'
        }
        return
      }

      // Remove opções existentes
      while (lknInstallmentSelect.options.length > 0) {
        lknInstallmentSelect.remove(0)
      }

      let validOptions = 0
      for (let i = 1; i <= lknInstallmentLimit; i++) {
        let finalInstallment
        let formatedInstallment
        let text
        let addOption = true

        // Se a versão PRO está ativa, usar cálculo complexo
        if (typeof lknWCCieloCredit !== 'undefined' && lknWCCieloCredit.licenseResult) {
          let installmentBase = (subtotalShipping - discountsTotal) / i
          finalInstallment = installmentBase + feesTotal + taxesTotal
          let hasCustomConfig = false
          for (let t = 0; t < lknInstallmentInterest.length; t++) {
            const installmentObj = lknInstallmentInterest[t]
            if (installmentObj.id === i) {
              if (installmentObj.label) {
                text = document.createTextNode(installmentObj.label)
                hasCustomConfig = true
              } else if (installmentObj.interest) {
                const interestAmount = (subtotalShipping - discountsTotal) + ((subtotalShipping) * (installmentObj.interest / 100))
                const interestInstallment = (interestAmount / i) + feesTotal + taxesTotal
                formatedInstallment = new Intl.NumberFormat('pt-br', { style: 'currency', currency: lknWCCieloCredit.currency }).format(interestInstallment)
                text = document.createTextNode(i + 'x de ' + formatedInstallment + ' (' + installmentObj.interest + '% de juros)')
                hasCustomConfig = true
              } else if (installmentObj.discount) {
                const discountAmount = (subtotalShipping - discountsTotal) - ((subtotalShipping) * (installmentObj.discount / 100))
                const discountInstallment = (discountAmount / i) + feesTotal + taxesTotal
                formatedInstallment = new Intl.NumberFormat('pt-br', { style: 'currency', currency: lknWCCieloCredit.currency }).format(discountInstallment)
                text = document.createTextNode(i + 'x de ' + formatedInstallment + ' (' + installmentObj.discount + '% de desconto)')
                hasCustomConfig = true
              }
              break
            }
          }
          if (!hasCustomConfig) {
            formatedInstallment = new Intl.NumberFormat('pt-br', { style: 'currency', currency: lknWCCieloCredit.currency }).format(finalInstallment)
            let defaultText = ' sem juros'
            if (typeof lknWCCieloCreditConfig !== 'undefined' && lknWCCieloCreditConfig.interest_or_discount === 'discount') {
              defaultText = ''
            }
            text = document.createTextNode(i + 'x de ' + formatedInstallment + defaultText)
          }
        } else {
          finalInstallment = (subtotalShipping - discountsTotal + feesTotal + taxesTotal) / i
          formatedInstallment = new Intl.NumberFormat('pt-br', { style: 'currency', currency: lknWCCieloCredit.currency }).format(finalInstallment)
          text = document.createTextNode(i + 'x de ' + formatedInstallment)
        }

        const checkValue = (typeof lknWCCieloCredit !== 'undefined' && lknWCCieloCredit.licenseResult) ?
          subtotalShipping : (subtotalShipping - discountsTotal + feesTotal + taxesTotal)
        if ((checkValue / (i + 1)) < lknInstallmentMin) {
          addOption = false
        }

        if (addOption) {
          const option = document.createElement('option')
          option.value = i
          option.appendChild(text)
          lknInstallmentSelect.appendChild(option)
          validOptions++

          if (typeof lknWCCieloCreditAjax !== 'undefined' &&
            lknWCCieloCreditAjax.current_installment &&
            i === parseInt(lknWCCieloCreditAjax.current_installment)) {
            option.selected = true
          }
        }
      }

      // Se só existe uma opção válida, não exibe o select
      const installmentRow = document.getElementById('lkn-cc-installment-row')
      if (validOptions <= 1) {
        if (installmentRow) {
          installmentRow.style.display = 'none'
        }
      } else {
        if (installmentRow) {
          installmentRow.style.display = ''
        }
      }
    }
  }
})(jQuery)
