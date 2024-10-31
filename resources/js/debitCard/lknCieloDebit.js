'use strict'

function _instanceof (left, right) { if (right != null && typeof Symbol !== 'undefined' && right[Symbol.hasInstance]) { return !!right[Symbol.hasInstance](left) } else { return left instanceof right } }
function _typeof (o) { '@babel/helpers - typeof'; return _typeof = typeof Symbol === 'function' && typeof Symbol.iterator === 'symbol' ? function (o) { return typeof o } : function (o) { return o && typeof Symbol === 'function' && o.constructor === Symbol && o !== Symbol.prototype ? 'symbol' : typeof o }, _typeof(o) }
function _createForOfIteratorHelper (o, allowArrayLike) { let it = typeof Symbol !== 'undefined' && o[Symbol.iterator] || o['@@iterator']; if (!it) { if (Array.isArray(o) || (it = _unsupportedIterableToArray(o)) || allowArrayLike && o && typeof o.length === 'number') { if (it) o = it; let i = 0; const F = function F () {}; return { s: F, n: function n () { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] } }, e: function e (_e) { throw _e }, f: F } } throw new TypeError('Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.') } let normalCompletion = true; let didErr = false; let err; return { s: function s () { it = it.call(o) }, n: function n () { const step = it.next(); normalCompletion = step.done; return step }, e: function e (_e2) { didErr = true; err = _e2 }, f: function f () { try { if (!normalCompletion && it.return != null) it.return() } finally { if (didErr) throw err } } } }
function _toConsumableArray (arr) { return _arrayWithoutHoles(arr) || _iterableToArray(arr) || _unsupportedIterableToArray(arr) || _nonIterableSpread() }
function _nonIterableSpread () { throw new TypeError('Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.') }
function _iterableToArray (iter) { if (typeof Symbol !== 'undefined' && iter[Symbol.iterator] != null || iter['@@iterator'] != null) return Array.from(iter) }
function _arrayWithoutHoles (arr) { if (Array.isArray(arr)) return _arrayLikeToArray(arr) }
function _regeneratorRuntime () { 'use strict'; /*! regenerator-runtime -- Copyright (c) 2014-present, Facebook, Inc. -- license (MIT): https://github.com/facebook/regenerator/blob/main/LICENSE */ _regeneratorRuntime = function _regeneratorRuntime () { return e }; let t; var e = {}; const r = Object.prototype; const n = r.hasOwnProperty; const o = Object.defineProperty || function (t, e, r) { t[e] = r.value }; const i = typeof Symbol === 'function' ? Symbol : {}; const a = i.iterator || '@@iterator'; const c = i.asyncIterator || '@@asyncIterator'; const u = i.toStringTag || '@@toStringTag'; function define (t, e, r) { return Object.defineProperty(t, e, { value: r, enumerable: !0, configurable: !0, writable: !0 }), t[e] } try { define({}, '') } catch (t) { define = function define (t, e, r) { return t[e] = r } } function wrap (t, e, r, n) { const i = e && _instanceof(e.prototype, Generator) ? e : Generator; const a = Object.create(i.prototype); const c = new Context(n || []); return o(a, '_invoke', { value: makeInvokeMethod(t, r, c) }), a } function tryCatch (t, e, r) { try { return { type: 'normal', arg: t.call(e, r) } } catch (t) { return { type: 'throw', arg: t } } } e.wrap = wrap; const h = 'suspendedStart'; const l = 'suspendedYield'; const f = 'executing'; const s = 'completed'; const y = {}; function Generator () {} function GeneratorFunction () {} function GeneratorFunctionPrototype () {} let p = {}; define(p, a, function () { return this }); const d = Object.getPrototypeOf; const v = d && d(d(values([]))); v && v !== r && n.call(v, a) && (p = v); const g = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(p); function defineIteratorMethods (t) { ['next', 'throw', 'return'].forEach(function (e) { define(t, e, function (t) { return this._invoke(e, t) }) }) } function AsyncIterator (t, e) { function invoke (r, o, i, a) { const c = tryCatch(t[r], t, o); if (c.type !== 'throw') { const u = c.arg; const h = u.value; return h && _typeof(h) == 'object' && n.call(h, '__await') ? e.resolve(h.__await).then(function (t) { invoke('next', t, i, a) }, function (t) { invoke('throw', t, i, a) }) : e.resolve(h).then(function (t) { u.value = t, i(u) }, function (t) { return invoke('throw', t, i, a) }) } a(c.arg) } let r; o(this, '_invoke', { value: function value (t, n) { function callInvokeWithMethodAndArg () { return new e(function (e, r) { invoke(t, n, e, r) }) } return r = r ? r.then(callInvokeWithMethodAndArg, callInvokeWithMethodAndArg) : callInvokeWithMethodAndArg() } }) } function makeInvokeMethod (e, r, n) { let o = h; return function (i, a) { if (o === f) throw new Error('Generator is already running'); if (o === s) { if (i === 'throw') throw a; return { value: t, done: !0 } } for (n.method = i, n.arg = a; ;) { const c = n.delegate; if (c) { const u = maybeInvokeDelegate(c, n); if (u) { if (u === y) continue; return u } } if (n.method === 'next') n.sent = n._sent = n.arg; else if (n.method === 'throw') { if (o === h) throw o = s, n.arg; n.dispatchException(n.arg) } else n.method === 'return' && n.abrupt('return', n.arg); o = f; const p = tryCatch(e, r, n); if (p.type === 'normal') { if (o = n.done ? s : l, p.arg === y) continue; return { value: p.arg, done: n.done } } p.type === 'throw' && (o = s, n.method = 'throw', n.arg = p.arg) } } } function maybeInvokeDelegate (e, r) { const n = r.method; const o = e.iterator[n]; if (o === t) return r.delegate = null, n === 'throw' && e.iterator.return && (r.method = 'return', r.arg = t, maybeInvokeDelegate(e, r), r.method === 'throw') || n !== 'return' && (r.method = 'throw', r.arg = new TypeError("The iterator does not provide a '" + n + "' method")), y; const i = tryCatch(o, e.iterator, r.arg); if (i.type === 'throw') return r.method = 'throw', r.arg = i.arg, r.delegate = null, y; const a = i.arg; return a ? a.done ? (r[e.resultName] = a.value, r.next = e.nextLoc, r.method !== 'return' && (r.method = 'next', r.arg = t), r.delegate = null, y) : a : (r.method = 'throw', r.arg = new TypeError('iterator result is not an object'), r.delegate = null, y) } function pushTryEntry (t) { const e = { tryLoc: t[0] }; 1 in t && (e.catchLoc = t[1]), 2 in t && (e.finallyLoc = t[2], e.afterLoc = t[3]), this.tryEntries.push(e) } function resetTryEntry (t) { const e = t.completion || {}; e.type = 'normal', delete e.arg, t.completion = e } function Context (t) { this.tryEntries = [{ tryLoc: 'root' }], t.forEach(pushTryEntry, this), this.reset(!0) } function values (e) { if (e || e === '') { const r = e[a]; if (r) return r.call(e); if (typeof e.next === 'function') return e; if (!isNaN(e.length)) { let o = -1; const i = function next () { for (; ++o < e.length;) if (n.call(e, o)) return next.value = e[o], next.done = !1, next; return next.value = t, next.done = !0, next }; return i.next = i } } throw new TypeError(_typeof(e) + ' is not iterable') } return GeneratorFunction.prototype = GeneratorFunctionPrototype, o(g, 'constructor', { value: GeneratorFunctionPrototype, configurable: !0 }), o(GeneratorFunctionPrototype, 'constructor', { value: GeneratorFunction, configurable: !0 }), GeneratorFunction.displayName = define(GeneratorFunctionPrototype, u, 'GeneratorFunction'), e.isGeneratorFunction = function (t) { const e = typeof t === 'function' && t.constructor; return !!e && (e === GeneratorFunction || (e.displayName || e.name) === 'GeneratorFunction') }, e.mark = function (t) { return Object.setPrototypeOf ? Object.setPrototypeOf(t, GeneratorFunctionPrototype) : (t.__proto__ = GeneratorFunctionPrototype, define(t, u, 'GeneratorFunction')), t.prototype = Object.create(g), t }, e.awrap = function (t) { return { __await: t } }, defineIteratorMethods(AsyncIterator.prototype), define(AsyncIterator.prototype, c, function () { return this }), e.AsyncIterator = AsyncIterator, e.async = function (t, r, n, o, i) { void 0 === i && (i = Promise); const a = new AsyncIterator(wrap(t, r, n, o), i); return e.isGeneratorFunction(r) ? a : a.next().then(function (t) { return t.done ? t.value : a.next() }) }, defineIteratorMethods(g), define(g, u, 'Generator'), define(g, a, function () { return this }), define(g, 'toString', function () { return '[object Generator]' }), e.keys = function (t) { const e = Object(t); const r = []; for (const n in e) r.push(n); return r.reverse(), function next () { for (; r.length;) { const t = r.pop(); if (t in e) return next.value = t, next.done = !1, next } return next.done = !0, next } }, e.values = values, Context.prototype = { constructor: Context, reset: function reset (e) { if (this.prev = 0, this.next = 0, this.sent = this._sent = t, this.done = !1, this.delegate = null, this.method = 'next', this.arg = t, this.tryEntries.forEach(resetTryEntry), !e) for (const r in this) r.charAt(0) === 't' && n.call(this, r) && !isNaN(+r.slice(1)) && (this[r] = t) }, stop: function stop () { this.done = !0; const t = this.tryEntries[0].completion; if (t.type === 'throw') throw t.arg; return this.rval }, dispatchException: function dispatchException (e) { if (this.done) throw e; const r = this; function handle (n, o) { return a.type = 'throw', a.arg = e, r.next = n, o && (r.method = 'next', r.arg = t), !!o } for (let o = this.tryEntries.length - 1; o >= 0; --o) { const i = this.tryEntries[o]; var a = i.completion; if (i.tryLoc === 'root') return handle('end'); if (i.tryLoc <= this.prev) { const c = n.call(i, 'catchLoc'); const u = n.call(i, 'finallyLoc'); if (c && u) { if (this.prev < i.catchLoc) return handle(i.catchLoc, !0); if (this.prev < i.finallyLoc) return handle(i.finallyLoc) } else if (c) { if (this.prev < i.catchLoc) return handle(i.catchLoc, !0) } else { if (!u) throw new Error('try statement without catch or finally'); if (this.prev < i.finallyLoc) return handle(i.finallyLoc) } } } }, abrupt: function abrupt (t, e) { for (let r = this.tryEntries.length - 1; r >= 0; --r) { const o = this.tryEntries[r]; if (o.tryLoc <= this.prev && n.call(o, 'finallyLoc') && this.prev < o.finallyLoc) { var i = o; break } } i && (t === 'break' || t === 'continue') && i.tryLoc <= e && e <= i.finallyLoc && (i = null); const a = i ? i.completion : {}; return a.type = t, a.arg = e, i ? (this.method = 'next', this.next = i.finallyLoc, y) : this.complete(a) }, complete: function complete (t, e) { if (t.type === 'throw') throw t.arg; return t.type === 'break' || t.type === 'continue' ? this.next = t.arg : t.type === 'return' ? (this.rval = this.arg = t.arg, this.method = 'return', this.next = 'end') : t.type === 'normal' && e && (this.next = e), y }, finish: function finish (t) { for (let e = this.tryEntries.length - 1; e >= 0; --e) { const r = this.tryEntries[e]; if (r.finallyLoc === t) return this.complete(r.completion, r.afterLoc), resetTryEntry(r), y } }, catch: function _catch (t) { for (let e = this.tryEntries.length - 1; e >= 0; --e) { const r = this.tryEntries[e]; if (r.tryLoc === t) { const n = r.completion; if (n.type === 'throw') { var o = n.arg; resetTryEntry(r) } return o } } throw new Error('illegal catch attempt') }, delegateYield: function delegateYield (e, r, n) { return this.delegate = { iterator: values(e), resultName: r, nextLoc: n }, this.method === 'next' && (this.arg = t), y } }, e }
function asyncGeneratorStep (gen, resolve, reject, _next, _throw, key, arg) { try { var info = gen[key](arg); var value = info.value } catch (error) { reject(error); return } if (info.done) { resolve(value) } else { Promise.resolve(value).then(_next, _throw) } }
function _asyncToGenerator (fn) { return function () { const self = this; const args = arguments; return new Promise(function (resolve, reject) { const gen = fn.apply(self, args); function _next (value) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, 'next', value) } function _throw (err) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, 'throw', err) } _next(undefined) }) } }
function _defineProperty (obj, key, value) { key = _toPropertyKey(key); if (key in obj) { Object.defineProperty(obj, key, { value, enumerable: true, configurable: true, writable: true }) } else { obj[key] = value } return obj }
function _toPropertyKey (arg) { const key = _toPrimitive(arg, 'string'); return _typeof(key) === 'symbol' ? key : String(key) }
function _toPrimitive (input, hint) { if (_typeof(input) !== 'object' || input === null) return input; const prim = input[Symbol.toPrimitive]; if (prim !== undefined) { const res = prim.call(input, hint || 'default'); if (_typeof(res) !== 'object') return res; throw new TypeError('@@toPrimitive must return a primitive value.') } return (hint === 'string' ? String : Number)(input) }
function _slicedToArray (arr, i) { return _arrayWithHoles(arr) || _iterableToArrayLimit(arr, i) || _unsupportedIterableToArray(arr, i) || _nonIterableRest() }
function _nonIterableRest () { throw new TypeError('Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.') }
function _unsupportedIterableToArray (o, minLen) { if (!o) return; if (typeof o === 'string') return _arrayLikeToArray(o, minLen); let n = Object.prototype.toString.call(o).slice(8, -1); if (n === 'Object' && o.constructor) n = o.constructor.name; if (n === 'Map' || n === 'Set') return Array.from(o); if (n === 'Arguments' || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen) }
function _arrayLikeToArray (arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i]; return arr2 }
function _iterableToArrayLimit (r, l) { let t = r == null ? null : typeof Symbol !== 'undefined' && r[Symbol.iterator] || r['@@iterator']; if (t != null) { let e; let n; let i; let u; const a = []; let f = !0; let o = !1; try { if (i = (t = t.call(r)).next, l === 0) { if (Object(t) !== t) return; f = !1 } else for (; !(f = (e = i.call(t)).done) && (a.push(e.value), a.length !== l); f = !0); } catch (r) { o = !0, n = r } finally { try { if (!f && t.return != null && (u = t.return(), Object(u) !== u)) return } finally { if (o) throw n } } return a } }
function _arrayWithHoles (arr) { if (Array.isArray(arr)) return arr }
const lknDCsettingsCielo = window.wc.wcSettings.getSetting('lkn_cielo_debit_data', {})
const lknDCLabelCielo = window.wp.htmlEntities.decodeEntities(lknDCsettingsCielo.title)
const lknDCAccessTokenCielo = window.wp.htmlEntities.decodeEntities(lknDCsettingsCielo.accessToken)
const lknDCActiveInstallmentCielo = window.wp.htmlEntities.decodeEntities(lknDCsettingsCielo.activeInstallment)
const lknDCUrlCielo = window.wp.htmlEntities.decodeEntities(lknDCsettingsCielo.url)
const lknDCTotalCartCielo = window.wp.htmlEntities.decodeEntities(lknDCsettingsCielo.totalCart)
const lknDCOrderNumberCielo = window.wp.htmlEntities.decodeEntities(lknDCsettingsCielo.orderNumber)
const lknDCDirScript3DSCielo = window.wp.htmlEntities.decodeEntities(lknDCsettingsCielo.dirScript3DS)
const lknDCInstallmentLimitCielo = window.wp.htmlEntities.decodeEntities(lknDCsettingsCielo.installmentLimit)
const lknCC3DSinstallmentsCielo = window.wp.htmlEntities.decodeEntities(lknDCsettingsCielo.installments)
const lknDCDirScriptConfig3DSCielo = window.wp.htmlEntities.decodeEntities(lknDCsettingsCielo.dirScriptConfig3DS)
const lknDCTranslationsDebitCielo = lknDCsettingsCielo.translations
const lknDCNonceCieloDebit = lknDCsettingsCielo.nonceCieloDebit
const lknDCTranslationsCielo = lknDCsettingsCielo.translations
const lknDCBec = window.wp.htmlEntities.decodeEntities(lknDCsettingsCielo.bec)
const lknDCClientIp = window.wp.htmlEntities.decodeEntities(lknDCsettingsCielo.client_ip)
const lknDCUserGuest = window.wp.htmlEntities.decodeEntities(lknDCsettingsCielo.user_guest)
const lknDCAuthMethod = window.wp.htmlEntities.decodeEntities(lknDCsettingsCielo.authentication_method)
const lknDCHideCheckoutButton = function lknDCHideCheckoutButton () {
  const lknDCElement = document.querySelectorAll('.wc-block-components-checkout-place-order-button')
  if (lknDCElement && lknDCElement[0]) {
    lknDCElement[0].style.display = 'none'
  }
}
const lknDCInitCieloPaymentForm = function lknDCInitCieloPaymentForm () {
  document.addEventListener('DOMContentLoaded', lknDCHideCheckoutButton)
  lknDCHideCheckoutButton()

  // Load Cielo 3DS BpMPI Script
  const scriptUrlBpmpi = lknDCDirScript3DSCielo
  const existingScriptBpmpi = document.querySelector('script[src="'.concat(scriptUrlBpmpi, '"]'))
  if (!existingScriptBpmpi) {
    const scriptBpmpi = document.createElement('script')
    scriptBpmpi.src = scriptUrlBpmpi
    scriptBpmpi.async = true
    document.body.appendChild(scriptBpmpi)
  }

  // Load Cielo 3DS Config Script
  const scriptUrl = lknDCDirScriptConfig3DSCielo
  const existingScript = document.querySelector('script[src="'.concat(scriptUrl, '"]'))
  if (!existingScript) {
    const script = document.createElement('script')
    script.src = scriptUrl
    script.async = true
    document.body.appendChild(script)
  }
}
const lknDCContentCielo = function lknDCContentCielo (props) {
  const wcComponents = window.wc.blocksComponents
  const eventRegistration = props.eventRegistration
  const emitResponse = props.emitResponse
  const onPaymentSetup = eventRegistration.onPaymentSetup
  const _window$wp$element$us = window.wp.element.useState([])
  const _window$wp$element$us2 = _slicedToArray(_window$wp$element$us, 2)
  const options = _window$wp$element$us2[0]
  const setOptions = _window$wp$element$us2[1]
  const _window$wp$element$us3 = window.wp.element.useState(0)
  const _window$wp$element$us4 = _slicedToArray(_window$wp$element$us3, 2)
  const cardBinState = _window$wp$element$us4[0]
  const setCardBinState = _window$wp$element$us4[1]
  const _window$wp$element$us5 = window.wp.element.useState([{
    key: 'Credit',
    label: lknDCTranslationsCielo.creditCard
  }, {
    key: 'Debit',
    label: lknDCTranslationsCielo.debitCard
  }])
  const _window$wp$element$us6 = _slicedToArray(_window$wp$element$us5, 2)
  const cardTypeOptions = _window$wp$element$us6[0]
  const setCardTypeOptions = _window$wp$element$us6[1]
  const _window$wp$element$us7 = window.wp.element.useState({
    lkn_dc_cardholder_name: '',
    lkn_dcno: '',
    lkn_dc_expdate: '',
    lkn_dc_cvc: '',
    lkn_cc_installments: '1',
    // Definir padrão como 1 parcela
    lkn_cc_type: 'Credit'
  })
  const _window$wp$element$us8 = _slicedToArray(_window$wp$element$us7, 2)
  const debitObject = _window$wp$element$us8[0]
  const setdebitObject = _window$wp$element$us8[1]
  const formatDebitCardNumber = function formatDebitCardNumber (value) {
    if (value?.length > 24) return debitObject.lkn_dcno
    // Remove caracteres não numéricos
    const cleanedValue = value?.replace(/\D/g, '')
    // Adiciona espaços a cada quatro dígitos
    const formattedValue = cleanedValue?.replace(/(.{4})/g, '$1 ')?.trim()
    return formattedValue
  }
  const updatedebitObject = function updatedebitObject (key, value) {
    switch (key) {
      case 'lkn_dc_cardholder_name':
        // Atualiza o estado
        setdebitObject(_defineProperty({}, key, value))
        break
      case 'lkn_dc_expdate':
        if (value.length > 7) return

        // Verifica se o valor é uma data válida (MM/YY)
        var isValidDate = /^\d{2}\/\d{2}$/.test(value)
        if (!isValidDate) {
          // Remove caracteres não numéricos
          const cleanedValue = value?.replace(/\D/g, '')
          let formattedValue = cleanedValue?.replace(/^(.{2})(.{2})$/, '$1 / $2')

          // Se o tamanho da string for 6 (MMYYYY), formate para MM / YY
          if (cleanedValue.length === 6) {
            formattedValue = cleanedValue?.replace(/^(.{2})(.{2})(.{2})$/, '$1 / $3')
          }

          // Atualiza o estado
          setdebitObject(_defineProperty({}, key, formattedValue))
        }
        return
      case 'lkn_dc_cvc':
        if (value.length > 8) return
      case 'lkn_dcno':
        if (value.length > 7) {
          const cardBin = value.replace(' ', '').substring(0, 6)
          const url = window.location.origin + '/wp-json/lknWCGatewayCielo/checkCard?cardbin=' + cardBin
          if (cardBin !== cardBinState) {
            setCardBinState(cardBin) // Mova o setCardBinState para antes da requisição

            fetch(url, {
              method: 'GET',
              headers: {
                Accept: 'application/json'
              }
            }).then(function (response) {
              if (!response.ok) {
                throw new Error('Network response was not ok ' + response.statusText)
              }
              return response.json()
            }).then(function (data) {
              if (data.CardType == 'Crédito') {
                setCardTypeOptions([{
                  key: 'Credit',
                  label: lknDCTranslationsCielo.creditCard
                }])
                setdebitObject(function (prevState) {
                  return {
                    ...prevState,
                    lkn_cc_type: 'Credit'
                  }
                })
              } else if (data.CardType == 'Débito') {
                setCardTypeOptions([{
                  key: 'Debit',
                  label: lknDCTranslationsCielo.debitCard
                }])
                setdebitObject(function (prevState) {
                  return {
                    ...prevState,
                    lkn_cc_type: 'Debit'
                  }
                })
              } else {
                setCardTypeOptions([{
                  key: 'Credit',
                  label: lknDCTranslationsCielo.creditCard
                }, {
                  key: 'Debit',
                  label: lknDCTranslationsCielo.debitCard
                }])
              }
            }).catch(function (error) {
              console.error('Erro:', error)
            })
          }
          setdebitObject(function (prevState) {
            return {
              ...prevState,
              lkn_dcno: value
            }
          })
        }
        break
      default:
        break
    }
    setdebitObject(_defineProperty({}, key, value))
  }
  window.wp.element.useEffect(function () {
    const lknDCElement = document.querySelectorAll('.wc-block-components-checkout-place-order-button')
    if (lknDCElement && lknDCElement[0]) {
      // Hides the checkout button on cielo debit select
      lknDCElement[0].style.display = 'none'

      // Shows the checkout button on payment change
      return function () {
        lknDCElement[0].style.display = ''
      }
    }
  })
  const handleButtonClick = function handleButtonClick () {
    // Verifica se todos os campos do debitObject estão preenchidos
    const allFieldsFilled = Object.keys(debitObject).filter(function (key) {
      return key !== 'lkn_dc_cardholder_name'
    }).every(function (key) {
      return debitObject[key].trim() !== ''
    })

    // Seleciona os lknDCElements dos campos de entrada
    const cardNumberInput = document.getElementById('lkn_dcno')
    const expDateInput = document.getElementById('lkn_dc_expdate')
    const cvvInput = document.getElementById('lkn_dc_cvc')
    const cardHolder = document.getElementById('lkn_dc_cardholder_name')

    // Remove classes de erro e mensagens de validação existentes
    cardNumberInput?.classList.remove('has-error')
    expDateInput?.classList.remove('has-error')
    cvvInput?.classList.remove('has-error')
    cardHolder?.classList.remove('has-error')
    if (allFieldsFilled) {
      lknDCProccessButton()
    } else {
      // Adiciona classes de erro aos campos vazios
      if (debitObject.lkn_dcno.trim() === '') {
        const parentDiv = cardNumberInput?.parentElement
        parentDiv?.classList.add('has-error')
      }
      if (debitObject.lkn_dc_expdate.trim() === '') {
        const _parentDiv = expDateInput?.parentElement
        _parentDiv?.classList.add('has-error')
      }
      if (debitObject.lkn_dc_cvc.trim() === '') {
        const _parentDiv2 = cvvInput?.parentElement
        _parentDiv2?.classList.add('has-error')
      }
    }
  }
  window.wp.element.useEffect(function () {
    lknDCInitCieloPaymentForm()
    const unsubscribe = onPaymentSetup(/* #__PURE__ */_asyncToGenerator(/* #__PURE__ */_regeneratorRuntime().mark(function _callee () {
      let Button3dsEnviar, paymentCavv, paymentEci, paymentReferenceId, paymentVersion, paymentXid
      return _regeneratorRuntime().wrap(function _callee$ (_context) {
        while (1) {
          switch (_context.prev = _context.next) {
            case 0:
              Button3dsEnviar = document.querySelectorAll('.wc-block-components-checkout-place-order-button')[0].closest('form')
              paymentCavv = Button3dsEnviar?.getAttribute('data-payment-cavv')
              paymentEci = Button3dsEnviar?.getAttribute('data-payment-eci')
              paymentReferenceId = Button3dsEnviar?.getAttribute('data-payment-ref_id')
              paymentVersion = Button3dsEnviar?.getAttribute('data-payment-version')
              paymentXid = Button3dsEnviar?.getAttribute('data-payment-xid')
              return _context.abrupt('return', {
                type: emitResponse.responseTypes.SUCCESS,
                meta: {
                  paymentMethodData: {
                  // TODO Corrigir campos faltando
                    lkn_dcno: debitObject.lkn_dcno,
                    lkn_dc_cardholder_name: debitObject.lkn_dc_cardholder_name,
                    lkn_dc_expdate: debitObject.lkn_dc_expdate,
                    lkn_dc_cvc: debitObject.lkn_dc_cvc,
                    nonce_lkn_cielo_debit: lknDCNonceCieloDebit,
                    lkn_cielo_3ds_cavv: paymentCavv,
                    lkn_cielo_3ds_eci: paymentEci,
                    lkn_cielo_3ds_ref_id: paymentReferenceId,
                    lkn_cielo_3ds_version: paymentVersion,
                    lkn_cielo_3ds_xid: paymentXid,
                    lkn_cc_installments: debitObject.lkn_cc_installments,
                    lkn_cc_type: debitObject.lkn_cc_type
                  }
                }
              })
            case 7:
            case 'end':
              return _context.stop()
          }
        }
      }, _callee)
    })))

    // Cancela a inscrição quando este componente é desmontado.
    return function () {
      unsubscribe()
    }
  }, [debitObject, emitResponse.responseTypes.ERROR, emitResponse.responseTypes.SUCCESS, onPaymentSetup])
  const calculateInstallments = function calculateInstallments (lknDCTotalCartCielo) {
    const installmentMin = 5
    // Verifica se 'lknCCActiveInstallmentCielo' é 'yes' e o valor total é maior que 10
    if (lknDCActiveInstallmentCielo === 'yes' && lknDCTotalCartCielo > 10) {
      const maxInstallments = lknDCInstallmentLimitCielo // Limita o parcelamento até 12 vezes, deixei fixo para teste
      const _loop = function _loop (index) {
        const installmentAmount = (lknDCTotalCartCielo / index).toLocaleString('pt-BR', {
          minimumFractionDigits: 2,
          maximumFractionDigits: 2
        })
        let nextInstallmentAmount = lknDCTotalCartCielo / index
        if (nextInstallmentAmount < installmentMin) {
          return 1 // break
        }
        let formatedInterest = false
        for (let t = 0; t < lknCC3DSinstallmentsCielo.length; t++) {
          const installmentObj = lknCC3DSinstallmentsCielo[t]
          if (installmentObj.id === index) {
            nextInstallmentAmount = (lknDCTotalCartCielo + lknDCTotalCartCielo * (parseFloat(installmentObj.interest) / 100)) / index
            formatedInterest = new Intl.NumberFormat('pt-br', {
              style: 'currency',
              currency: 'BRL'
            }).format(nextInstallmentAmount)
          }
        }
        if (formatedInterest) {
          setOptions(function (prevOptions) {
            return [].concat(_toConsumableArray(prevOptions), [{
              key: index,
              label: ''.concat(index, 'x de ').concat(formatedInterest)
            }])
          })
        } else {
          setOptions(function (prevOptions) {
            return [].concat(_toConsumableArray(prevOptions), [{
              key: index,
              label: ''.concat(index, 'x de R$ ').concat(installmentAmount, ' sem juros')
            }])
          })
        }
      }
      for (let index = 1; index <= maxInstallments; index++) {
        if (_loop(index)) break
      }
    } else {
      setOptions(function (prevOptions) {
        return [].concat(_toConsumableArray(prevOptions), [{
          key: '1',
          label: '1x de R$ '.concat(lknDCTotalCartCielo, ' (\xE0 vista)')
        }])
      })
    }
  }
  window.wp.element.useEffect(function () {
    calculateInstallments(lknDCTotalCartCielo)
    var intervalId = setInterval(function () {
      const targetNode = document.querySelector('.wc-block-formatted-money-amount.wc-block-components-formatted-money-amount.wc-block-components-totals-footer-item-tax-value')
      // Configuração do observer: quais mudanças serão observadas
      if (targetNode) {
        const config = {
          childList: true,
          subtree: true,
          characterData: true
        }
        const changeValue = function changeValue () {
          setOptions([])
          // Remover tudo exceto os números e a vírgula
          let valorNumerico = targetNode.textContent.replace(/[^\d,]/g, '')

          // Substituir a vírgula por um ponto
          valorNumerico = valorNumerico.replace(',', '.')

          // Converter para número
          valorNumerico = parseFloat(valorNumerico)
          calculateInstallments(valorNumerico)
        }
        changeValue()

        // Função de callback que será executada quando ocorrerem mudanças
        const callback = function callback (mutationsList, observer) {
          const _iterator = _createForOfIteratorHelper(mutationsList)
          let _step
          try {
            for (_iterator.s(); !(_step = _iterator.n()).done;) {
              const mutation = _step.value
              if (mutation.type === 'childList' || mutation.type === 'characterData') {
                changeValue()
              }
            }
          } catch (err) {
            _iterator.e(err)
          } finally {
            _iterator.f()
          }
        }

        // Cria uma instância do observer e o conecta ao nó alvo
        const observer = new MutationObserver(callback)
        observer.observe(targetNode, config)
        clearInterval(intervalId)
      }
    }, 500)
  }, [])
  return /* #__PURE__ */React.createElement(React.Fragment, null, /* #__PURE__ */React.createElement('div', null, /* #__PURE__ */React.createElement('h4', null, 'Pagamento processado pela Cielo API 3.0')), /* #__PURE__ */React.createElement(wcComponents.TextInput, {
    id: 'lkn_dc_cardholder_name',
    label: lknDCTranslationsDebitCielo.cardHolder,
    value: debitObject.lkn_dc_cardholder_name,
    onChange: function onChange (value) {
      updatedebitObject('lkn_dc_cardholder_name', value)
    },
    required: true
  }), /* #__PURE__ */React.createElement(wcComponents.TextInput, {
    id: 'lkn_dcno',
    label: lknDCTranslationsDebitCielo.cardNumber,
    value: debitObject.lkn_dcno,
    onChange: function onChange (value) {
      updatedebitObject('lkn_dcno', formatDebitCardNumber(value))
    },
    required: true
  }), /* #__PURE__ */React.createElement(wcComponents.TextInput, {
    id: 'lkn_dc_expdate',
    label: lknDCTranslationsDebitCielo.cardExpiryDate,
    value: debitObject.lkn_dc_expdate,
    onChange: function onChange (value) {
      updatedebitObject('lkn_dc_expdate', value)
    },
    required: true
  }), /* #__PURE__ */React.createElement(wcComponents.TextInput, {
    id: 'lkn_dc_cvc',
    label: lknDCTranslationsDebitCielo.securityCode,
    value: debitObject.lkn_dc_cvc,
    onChange: function onChange (value) {
      updatedebitObject('lkn_dc_cvc', value)
    },
    required: true
  }), /* #__PURE__ */React.createElement('div', {
    style: {
      marginBottom: '30px'
    }
  }), /* #__PURE__ */React.createElement(wcComponents.SortSelect, {
    id: 'lkn_cc_type',
    label: lknDCTranslationsCielo.cardType,
    value: debitObject.lkn_cc_type,
    onChange: function onChange (event) {
      updatedebitObject('lkn_cc_type', event.target.value)
    },
    options: cardTypeOptions
  }), /* #__PURE__ */React.createElement('div', {
    style: {
      marginBottom: '30px'
    }
  }), lknDCActiveInstallmentCielo === 'yes' && debitObject.lkn_cc_type == 'Credit' && /* #__PURE__ */React.createElement(wcComponents.SortSelect, {
    id: 'lkn_cc_installments',
    label: lknDCTranslationsCielo.cardType,
    value: debitObject.lkn_cc_installments,
    onChange: function onChange (event) {
      updatedebitObject('lkn_cc_installments', event.target.value)
    },
    options
  }), /* #__PURE__ */React.createElement('div', {
    style: {
      marginBottom: '30px'
    }
  }), /* #__PURE__ */React.createElement('div', {
    style: {
      display: 'flex',
      justifyContent: 'center'
    }
  }, /* #__PURE__ */React.createElement(wcComponents.Button, {
    id: 'sendOrder',
    onClick: handleButtonClick
  }, /* #__PURE__ */React.createElement('span', null, 'Finalizar pedido'))), /* #__PURE__ */React.createElement('div', {
    style: {
      marginBottom: '20px'
    }
  }), /* #__PURE__ */React.createElement('div', null, /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    name: 'lkn_auth_enabled',
    className: 'bpmpi_auth',
    value: 'true'
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    name: 'lkn_auth_enabled_notifyonly',
    className: 'bpmpi_auth_notifyonly',
    value: 'true'
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    name: 'lkn_auth_suppresschallenge',
    className: 'bpmpi_auth_suppresschallenge',
    value: 'false'
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    name: 'lkn_access_token',
    className: 'bpmpi_accesstoken',
    value: lknDCAccessTokenCielo
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    size: '50',
    name: 'lkn_order_number',
    className: 'bpmpi_ordernumber',
    value: lknDCOrderNumberCielo
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    name: 'lkn_currency',
    className: 'bpmpi_currency',
    value: 'BRL'
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    size: '50',
    id: 'lkn_cielo_3ds_value',
    name: 'lkn_amount',
    className: 'bpmpi_totalamount',
    value: lknDCTotalCartCielo
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    size: '2',
    name: 'lkn_installments',
    className: 'bpmpi_installments',
    value: '1'
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    name: 'lkn_payment_method',
    className: 'bpmpi_paymentmethod',
    value: 'Debit'
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    id: 'lkn_bpmpi_cardnumber',
    className: 'bpmpi_cardnumber'
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    id: 'lkn_bpmpi_expmonth',
    maxLength: '2',
    name: 'lkn_card_expiry_month',
    className: 'bpmpi_cardexpirationmonth'
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    id: 'lkn_bpmpi_expyear',
    maxLength: '4',
    name: 'lkn_card_expiry_year',
    className: 'bpmpi_cardexpirationyear'
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    id: 'lkn_bpmpi_default_card',
    name: 'lkn_default_card',
    className: 'bpmpi_default_card',
    value: 'false'
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    id: 'lkn_bpmpi_order_recurrence',
    name: 'lkn_order_recurrence',
    className: 'bpmpi_order_recurrence',
    value: 'false'
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    size: '50',
    className: 'bpmpi_order_productcode',
    value: 'PHY'
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    size: '50',
    className: 'bpmpi_transaction_mode',
    value: 'S'
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    size: '50',
    className: 'bpmpi_merchant_url',
    value: lknDCUrlCielo
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    size: '14',
    id: 'lkn_bpmpi_billto_customerid',
    name: 'lkn_card_customerid',
    className: 'bpmpi_billto_customerid'
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    size: '120',
    id: 'lkn_bpmpi_billto_contactname',
    name: 'lkn_card_contactname',
    className: 'bpmpi_billto_contactname'
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    size: '15',
    id: 'lkn_bpmpi_billto_phonenumber',
    name: 'lkn_card_phonenumber',
    className: 'bpmpi_billto_phonenumber'
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    size: '255',
    id: 'lkn_bpmpi_billto_email',
    name: 'lkn_card_email',
    className: 'bpmpi_billto_email'
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    size: '60',
    id: 'lkn_bpmpi_billto_street1',
    name: 'lkn_card_billto_street1',
    className: 'bpmpi_billto_street1'
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    size: '60',
    id: 'lkn_bpmpi_billto_street2',
    name: 'lkn_card_billto_street2',
    className: 'bpmpi_billto_street2'
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    size: '50',
    id: 'lkn_bpmpi_billto_city',
    name: 'lkn_card_billto_city',
    className: 'bpmpi_billto_city'
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    size: '2',
    id: 'lkn_bpmpi_billto_state',
    name: 'lkn_card_billto_state',
    className: 'bpmpi_billto_state'
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    size: '8',
    id: 'lkn_bpmpi_billto_zipcode',
    name: 'lkn_card_billto_zipcode',
    className: 'bpmpi_billto_zipcode'
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    size: '2',
    id: 'lkn_bpmpi_billto_country',
    name: 'lkn_card_billto_country',
    className: 'bpmpi_billto_country'
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    id: 'lkn_bpmpi_shipto_sameasbillto',
    name: 'lkn_card_shipto_sameasbillto',
    className: 'bpmpi_shipto_sameasbillto',
    value: 'true'
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    id: 'lkn_bpmpi_useraccount_guest',
    name: 'lkn_card_useraccount_guest',
    className: 'bpmpi_useraccount_guest',
    value: lknDCUserGuest
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    id: 'lkn_bpmpi_useraccount_authenticationmethod',
    name: 'lkn_card_useraccount_authenticationmethod',
    className: 'bpmpi_useraccount_authenticationmethod',
    value: lknDCAuthMethod
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    size: '45',
    id: 'lkn_bpmpi_device_ipaddress',
    name: 'lkn_card_device_ipaddress',
    className: 'bpmpi_device_ipaddress',
    value: lknDCClientIp
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    size: '7',
    id: 'lkn_bpmpi_device_channel',
    name: 'lkn_card_device_channel',
    className: 'bpmpi_device_channel',
    value: 'Browser'
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    size: '10',
    id: 'lkn_bpmpi_brand_establishment_code',
    name: 'lkn_card_brand_establishment_code',
    className: 'bpmpi_brand_establishment_code',
    value: lknDCBec
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    id: 'lkn_cavv',
    name: 'lkn_cielo_3ds_cavv',
    value: true
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    id: 'lkn_eci',
    name: 'lkn_cielo_3ds_eci',
    value: true
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    id: 'lkn_ref_id',
    name: 'lkn_cielo_3ds_ref_id',
    value: true
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    id: 'lkn_version',
    name: 'lkn_cielo_3ds_version',
    value: true
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    id: 'lkn_xid',
    name: 'lkn_cielo_3ds_xid',
    value: true
  })))
}
const Lkn_DC_Block_Gateway_Cielo = {
  name: 'lkn_cielo_debit',
  label: lknDCLabelCielo,
  content: window.wp.element.createElement(lknDCContentCielo),
  edit: window.wp.element.createElement(lknDCContentCielo),
  canMakePayment: function canMakePayment () {
    return true
  },
  ariaLabel: lknDCLabelCielo,
  supports: {
    features: lknDCsettingsCielo.supports
  }
}
window.wc.wcBlocksRegistry.registerPaymentMethod(Lkn_DC_Block_Gateway_Cielo)
