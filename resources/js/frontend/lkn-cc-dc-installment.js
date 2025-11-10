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
    $(document).on('change', '#lkn_cc_dc_installments', function () {
      const installment = $(this).val()
      const paymentMethod = 'lkn_cielo_debit'

      if (installment && typeof lknWCCielo3dsAjax !== 'undefined') {
        lknWCCieloUpdateInstallmentSession(paymentMethod, installment)
      }
    })
  }

  // Função para atualizar a sessão com a parcela selecionada
  function lknWCCieloUpdateInstallmentSession(paymentMethod, installment) {
    if (typeof lknWCCielo3dsAjax === 'undefined') {
      return
    }

    // Verificar se a parcela já é a mesma da sessão (evitar trigger desnecessário)
    if (lknWCCielo3dsAjax.current_installment === installment) {
      return
    }

    $.ajax({
      url: lknWCCielo3dsAjax.ajaxurl,
      type: 'POST',
      data: {
        action: 'lkn_update_payment_fees',
        nonce: lknWCCielo3dsAjax.nonce,
        payment_method: paymentMethod,
        installment: installment
      },
      success: function (response) {
        if (response.success) {
          // Atualizar a parcela atual em memória
          lknWCCielo3dsAjax.current_installment = installment
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
        let finalInstallment
        let formatedInstallment
        let text

        // Se a versão PRO está ativa, usar cálculo complexo
        if (typeof lknWCCielo3ds !== 'undefined' && lknWCCielo3ds.licenseResult) {
          // Calcular parcela base: (subtotal + frete) / parcelas + fees externo - descontos + taxes
          let installmentBase = subtotalShipping / i
          // Valor final da parcela (fees somados, descontos subtraídos, taxes somados)
          finalInstallment = installmentBase + feesTotal - discountsTotal + taxesTotal

          // Verificar se tem configuração específica de juros/desconto para esta parcela
          let hasCustomConfig = false
          for (let t = 0; t < lknInstallmentInterest.length; t++) {
            const installmentObj = lknInstallmentInterest[t]
            if (installmentObj.id === i) {
              if (installmentObj.label) {
                text = document.createTextNode(installmentObj.label)
                hasCustomConfig = true
              } else if (installmentObj.interest) {
                // Calcular juros apenas sobre subtotal + frete, depois somar fees, subtrair descontos e somar taxes
                const interestAmount = subtotalShipping + (subtotalShipping * (installmentObj.interest / 100))
                const interestInstallment = (interestAmount / i) + feesTotal - discountsTotal + taxesTotal
                formatedInstallment = new Intl.NumberFormat('pt-br', { style: 'currency', currency: 'BRL' }).format(interestInstallment)
                text = document.createTextNode(i + 'x de ' + formatedInstallment + ' (' + installmentObj.interest + '% de juros)')
                hasCustomConfig = true
              } else if (installmentObj.discount) {
                // Calcular desconto apenas sobre subtotal + frete, depois somar fees, subtrair descontos e somar taxes
                const discountAmount = subtotalShipping - (subtotalShipping * (installmentObj.discount / 100))
                const discountInstallment = (discountAmount / i) + feesTotal - discountsTotal + taxesTotal
                formatedInstallment = new Intl.NumberFormat('pt-br', { style: 'currency', currency: 'BRL' }).format(discountInstallment)
                text = document.createTextNode(i + 'x de ' + formatedInstallment + ' (' + installmentObj.discount + '% de desconto)')
                hasCustomConfig = true
              }
              break
            }
          }

          // Se não tem configuração customizada, usar o texto padrão
          if (!hasCustomConfig) {
            formatedInstallment = new Intl.NumberFormat('pt-br', { style: 'currency', currency: 'BRL' }).format(finalInstallment)
            // Texto dinâmico baseado na configuração
            let defaultText = ' sem juros' // padrão
            if (typeof lknWCCielo3dsConfig !== 'undefined' && lknWCCielo3dsConfig.interest_or_discount === 'discount') {
              defaultText = ' sem desconto'
            }
            text = document.createTextNode(i + 'x de ' + formatedInstallment + defaultText)
          }
        } else {
          // Versão gratuita: inclui fees externos, descontos e taxes, mas não aplica juros/desconto do plugin
          // Calcular: (subtotal + frete + fees externos - descontos + taxes) / parcelas
          finalInstallment = (subtotalShipping + feesTotal - discountsTotal + taxesTotal) / i
          formatedInstallment = new Intl.NumberFormat('pt-br', { style: 'currency', currency: 'BRL' }).format(finalInstallment)
          text = document.createTextNode(i + 'x de ' + formatedInstallment)
        }

        const option = document.createElement('option')
        option.value = i
        option.appendChild(text)
        lknInstallmentSelect.appendChild(option)

        // Verificar se é a parcela atual da sessão e selecionar
        if (typeof lknWCCielo3dsAjax !== 'undefined' &&
          lknWCCielo3dsAjax.current_installment &&
          i === parseInt(lknWCCielo3dsAjax.current_installment)) {
          option.selected = true
        }

        // Para versão gratuita, usar o total completo para verificar mínimo
        const checkValue = (typeof lknWCCielo3ds !== 'undefined' && lknWCCielo3ds.licenseResult) ?
          subtotalShipping : (subtotalShipping + feesTotal - discountsTotal + taxesTotal)
        if ((checkValue / (i + 1)) < lknInstallmentMin) {
          break
        }
      }
    }
  }
})(jQuery)
