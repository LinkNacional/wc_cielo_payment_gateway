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
    const lknTotal = document.getElementById('lkn_cc_dc_installment_total')
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
      const amount = parseFloat(lknTotal.value)
      for (let c = 1; c < lknInstallmentSelect.childNodes.length; c + 2) {
        const childNode = lknInstallmentSelect.childNodes[c]
        lknInstallmentSelect.removeChild(childNode)
      }

      for (let i = 1; i <= lknInstallmentLimit; i++) {
        const installment = amount / i
        const formatedInstallment = new Intl.NumberFormat('pt-br', { style: 'currency', currency: 'BRL' }).format(installment)
        const option = document.createElement('option')
        let text = document.createTextNode(i + 'x ' + formatedInstallment + ' sem juros')
        if(lknWCCielo3dsDiscount == 'yes'){
          text = document.createTextNode(i + 'x ' + formatedInstallment)
        }
        for (let t = 0; t < lknInstallmentInterest.length; t++) {
          const installmentObj = lknInstallmentInterest[t]
          // Verify if it is the right installment
          if (installmentObj.id === i) {
            if (installmentObj.label) {
              text = document.createTextNode(installmentObj.label)
            } else if(installmentObj.interest) {
              const interest = (amount + (amount * (installmentObj.interest / 100))) / i
              const formatedInterest = new Intl.NumberFormat('pt-br', { style: 'currency', currency: 'BRL' }).format(interest)

              text = document.createTextNode(i + 'x ' + formatedInterest)
            } else if(installmentObj.discount) {
              const discount = (amount - (amount * (installmentObj.discount / 100))) / i
              const formatedDiscount = new Intl.NumberFormat('pt-br', { style: 'currency', currency: 'BRL' }).format(discount)

              text = document.createTextNode(i + 'x ' + formatedDiscount)
            }
          }
        }

        option.value = i
        option.appendChild(text)
        lknInstallmentSelect.appendChild(option)
        if ((amount / (i + 1)) < lknInstallmentMin) {
          break
        }
      }
    }
  }
})(jQuery)
