// Define globally accessible functions
window.getCookie = function (name) {
    const value = `; ${document.cookie}`
    const parts = value.split(`; ${name}=`)
    if (parts.length === 2) return parts.pop().split(';').shift()
}

window.change_symbol_to_text = function (symbol, showTextOnly = false) {
    const currencySymbol = {
        USD: '$',
        EUR: '€',
        GBP: '£',
    }
    for (let currencyCode in currencySymbol) {
        if (currencySymbol[currencyCode] === symbol) {
            if (showTextOnly) return currencyCode
            return `Price(${currencyCode})`
        }
    }
    return `Price(EUR)`
}

window.convertCurrency = function (amount, isAmountOnly = false) {
    const rates = window.rezdy_currency_rates
    const baseCurrency = 'EUR'
    const selectedCurrency = window.getCookie('rezdy_selected_currency') || window.rezdy_base_currency || 'EUR'
    const currencySigns = {
        USD: '$',
        EUR: '€',
        GBP: '£',
    }
    const currencySign = currencySigns[selectedCurrency] || '€'

    if (selectedCurrency == 'EUR') {
        if (isAmountOnly) {
            return amount
        }
        return [currencySign, amount]
    }

    if (!rates || !rates[baseCurrency] || !rates[selectedCurrency]) {
        if (isAmountOnly) {
            return amount
        }
        return [currencySign, amount]
    }
    const eurAmount = amount / rates[baseCurrency]
    const convertedAmount = (eurAmount * rates[selectedCurrency]).toFixed(2)
    if (isAmountOnly) {
        return convertedAmount
    }
    return [currencySign, convertedAmount]
}

window.setCookie = function (name, value, days) {
    const date = new Date()
    date.setTime(date.getTime() + days * 24 * 60 * 60 * 1000)
    const expires = `expires=${date.toUTCString()}`
    document.cookie = `${name}=${encodeURIComponent(value)}; ${expires}; path=/`
}

const rezdyCurrency = {
    init: function () {
        this.rates = currencyData.rates || {}
        this.baseCurrency = currencyData.baseCurrency || 'EUR'
        this.priceSelectors = currencyData.selectors || []
        this.selectedCurrency = window.getCookie('rezdy_selected_currency') || this.baseCurrency
        this.setGlobals()
        this.convertPrices()
        this.setDefaultCurrency()
        this.observeDOMChanges()
    },
    setGlobals: function () {
        window.rezdy_currency_rates = this.rates
        window.rezdy_base_currency = this.baseCurrency
        window.rezdy_selected_currency = this.selectedCurrency
    },
    convertPrices: function () {
        this.priceSelectors.forEach((priceSelector) => {
            const elements = document.querySelectorAll(priceSelector)
            elements.forEach((element) => {
                if (element.dataset.converted) return
                elementsFormatter.check(priceSelector, element)
            })
        })
    },
    setDefaultCurrency: function () {
        const currency = window.convertCurrency(0)
        const currencyText = window.change_symbol_to_text(currency[0], true)
        window.rezdy_currency_text = currencyText || 'EUR'
        window.rezdy_currency_symbol = currency[0] || '€'
        window.setCookie('rezdy_currency_text', window.rezdy_currency_text, 365)
        window.setCookie('rezdy_currency_symbol', window.rezdy_currency_symbol, 365)
    },
    observeDOMChanges: function () {
        const observer = new MutationObserver(() => {
            this.convertPrices()
        })
        observer.observe(document.body, {
            childList: true,
            subtree: true,
        })
    },
}

const elementsFormatter = {
    check: function (selector, el) {
        if (selector == '.icon_price .price_exprnce') {
            if (! el.querySelector('span')) {
                cdtFormatter.iconPricePriceNoSpan(el)
            } else {
                cdtFormatter.iconPricePriceWithSpan(el)
            }
        } else if (selector == '.gift-card-row .gift-amount') {
            tipsyFormatter.giftButtons(el)
        } else if (selector == '.class-loop-prices div .elementor-heading-title') {
            if (! el.querySelector('span')) {
                rwcFormatter.classLoopNoSpan(el)
            } else {
                rwcFormatter.classLoopWithSpan(el)
            }
        } else if (selector == '.product-sticky-price .elementor-heading-title') {
            jtrFormatter.stickyPrice(el)
        } else if (selector == '.product .form-right_tour .booktour_popup > h2') {
            rwcFormatter.floatingPrices(el)
        }
    }
}

const jtrFormatter = {
    stickyPrice: function (el) {
        const text = el.textContent
        const amount = Number(text.replace('€', '').trim())
        if (isNaN(amount)) {
            return
        }
        const convertedAmount = window.convertCurrency(amount)
        el.textContent = `${convertedAmount[0]}${convertedAmount[1]}`
        el.setAttribute('data-converted', true)
    }
}

const cdtFormatter = {
    iconPricePriceNoSpan: function (el) {
        const text = el.innerText
        const textSplit = text.split('€')
        const preText = textSplit[0].trim()
        const amount = Number(textSplit[1])
        if (isNaN(amount)) {
            return
        }
        const convertedAmount = window.convertCurrency(amount)
        el.innerText = `${preText} ${convertedAmount[0]}${convertedAmount[1]}`
        el.setAttribute('data-converted', true)
    },
    iconPricePriceWithSpan: function (el) {
        const children = el.childNodes
        const preText = children[0].textContent.trim()
        const compareText = el.querySelector('.compare_price').textContent.replace('€', '').trim()
        const regularText =children[2].textContent.replace('€', '').trim()
        if (isNaN(compareText) || isNaN(regularText)) {
            return
        }
        const compareConverted = window.convertCurrency(compareText)
        const regularConverted = window.convertCurrency(regularText)

        el.innerHTML = `${preText} <span class="compare_price">${compareConverted[0]}${compareConverted[1]}</span> ${regularConverted[0]}${regularConverted[1]}`
        el.setAttribute('data-converted', true)
    }
}

const tipsyFormatter = {
    giftButtons: function (el) {
        const text = el.textContent
        const amount = Number(text.replace('€', '').trim())
        if (isNaN(amount)) {
            return
        }
        const convertedAmount = window.convertCurrency(amount)
        el.textContent = `${convertedAmount[0]}${convertedAmount[1]}`
        el.setAttribute('data-amount', convertedAmount[1])
        el.setAttribute('data-converted', true)
    }
}

const rwcFormatter = {
    classLoopNoSpan: function (el) {
        const text = el.textContent
        const amount = Number(text.replace('€', '').trim())
        if (isNaN(amount)) {
            return
        }
        const convertedAmount = window.convertCurrency(amount)
        el.textContent = `${convertedAmount[0]}${convertedAmount[1]}`
        el.setAttribute('data-converted', true)
    },
    classLoopWithSpan: function (el) {
        const children = el.childNodes
        const preText = children[0]
        const amountText = children[1]
        const amount = Number(amountText.textContent.replace('€', '').trim())
        if (isNaN(amount)) {
            return
        }
        const convertedAmount = window.convertCurrency(amount)
        el.innerHTML = `${preText.innerHTML} ${convertedAmount[0]}${convertedAmount[1]}`
        el.setAttribute('data-converted', true)
    },
    floatingPrices: function (el) {
        const children = el.childNodes
        const preText = children[0].textContent.trim()
        const amount = Number(preText.replace('€', ''))
        if (isNaN(amount)) {
            return
        }
        const convertedAmount = window.convertCurrency(amount)
        const convertedText = `${convertedAmount[0]}${convertedAmount[1]}`
        const insidePreText = children[1].querySelector('.me-1').textContent.trim()
        const insideAmount = Number(insidePreText.replace('€', ''))
        if (isNaN(insideAmount)) {
            return
        }
        const insideConvertedAmount = window.convertCurrency(insideAmount)
        el.childNodes[0].textContent = convertedText
        el.childNodes[1].querySelector('.me-1').textContent = `${insideConvertedAmount[0]}${insideConvertedAmount[1]}`
        el.setAttribute('data-converted', true)
    }
}

document.addEventListener('DOMContentLoaded', () => {
    // Initialize the functionality
    rezdyCurrency.init()
})
