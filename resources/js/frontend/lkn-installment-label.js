/**
 * Script otimizado para testar hooks do WooCommerce Blocks - Cielo Payment Gateway
 */
document.addEventListener('DOMContentLoaded', function () {

    let isInitialized = false;
    let lastSelectedMethod = null;

    // Função para verificar se método Cielo está selecionado
    function isCieloMethodSelected() {
        // Verificar se há radios de pagamento selecionados
        const selectedPaymentRadio = document.querySelector('input[name="radio-control-wc-payment-method-options"]:checked');

        if (selectedPaymentRadio) {
            const selectedMethod = selectedPaymentRadio.value;

            // Verificar se é um método Cielo (credit ou debit)
            const isCielo = selectedMethod === 'lkn_cielo_credit' || selectedMethod === 'lkn_cielo_debit';
            return isCielo;
        }

        return false;
    }

    // Função para verificar se é cartão de débito (deve mostrar "À vista")
    function isDebitCardSelected() {
        const selectedPaymentRadio = document.querySelector('input[name="radio-control-wc-payment-method-options"]:checked');
        if (!selectedPaymentRadio) {
            return false;
        }
        
        const selectedMethod = selectedPaymentRadio.value;
        
        // Se é cielo_credit, sempre é crédito (mostrar parcelamento normal)
        if (selectedMethod === 'lkn_cielo_credit') {
            return false;
        }
        
        // Se é cielo_debit, verificar o select de tipo
        if (selectedMethod === 'lkn_cielo_debit') {
            const cardTypeSelect = document.querySelector('.lkn-credit-debit-card-type-select select');
            const isDebit = cardTypeSelect && cardTypeSelect.value === 'Debit';
            return isDebit;
        }
        
        return false;
    }

    // Função para obter informações de parcelamento do select
    function getInstallmentInfo() {
        // Se é cartão de débito, NÃO mostrar nenhuma label
        if (isDebitCardSelected()) {
            return null;
        }
        
        // Buscar pelas divs dos métodos Cielo
        const cieloSelects = document.querySelectorAll('.lkn_cielo_credit_select, .lkn_cielo_debit_select');

        for (let cieloDiv of cieloSelects) {
            // Verificar se está carregando (skeleton)
            const skeleton = cieloDiv.querySelector('.wc-block-components-skeleton__element');
            if (skeleton) {
                return { text: lknInstallmentLabelTranslations.loading, isLoading: true };
            }

            // Buscar o select dentro da div
            const select = cieloDiv.querySelector('select');
            
            if (select) {
                const selectedOption = select.options[select.selectedIndex];
                
                if (selectedOption) {
                    const optionText = selectedOption.textContent || selectedOption.innerText;
                    const selectedValue = selectedOption.value;

                    // Verificar se ainda está com texto de loading
                    if (selectedValue === 'loading' || optionText.includes('🔄') ||
                        optionText.includes('Calculando parcelas') || optionText.includes('Calculating installments')) {
                        return { text: lknInstallmentLabelTranslations.calculatingInstallments, isLoading: true };
                    }

                    // Extrair apenas a parte do parcelamento, removendo juros/descontos
                    let cleanText = optionText
                        .replace(/\s*\(.*?\)\s*/g, '') // Remove tudo entre parênteses
                        .replace(/\s*sem\s+juros\s*/gi, '') // Remove "sem juros"
                        .replace(/\s*sem\s+desconto\s*/gi, '') // Remove "sem desconto"
                        .replace(/\s*no\s+interest\s*/gi, '') // Remove "no interest"
                        .replace(/\s*no\s+discount\s*/gi, '') // Remove "no discount"
                        .replace(/\s*à\s+vista\s*/gi, '') // Remove "à vista"
                        .replace(/&nbsp;/g, ' ') // Replace HTML space
                        .replace(/🔄/g, '') // Remove emoji de loading
                        .trim();

                    // Se for valor 1, mostrar como "À vista"
                    if (selectedValue === '1') {
                        return { text: lknInstallmentLabelTranslations.cashPayment, isLoading: false, value: selectedValue };
                    } else {
                        return { text: cleanText, isLoading: false, value: selectedValue };
                    }
                }
            }
        }

        return { text: lknInstallmentLabelTranslations.fallbackInstallment, isLoading: false, value: '2' }; // fallback
    }

    // Função para inserir skeleton de loading
    function insertLoadingSkeleton(totalDiv) {
        // Verificar se já existe skeleton
        if (totalDiv.parentNode.querySelector('.cielo-payment-info-blocks')) {
            return;
        }

        // Criar skeleton no formato do WooCommerce com animação
        const loadingSkeleton = document.createElement('div');
        loadingSkeleton.className = 'wc-block-components-totals-item wc-block-components-totals-footer-item cielo-payment-info-blocks loading-skeleton';
        loadingSkeleton.style.fontSize = 'small';

        // Adicionar CSS de animação diretamente no elemento
        const animationStyle = document.createElement('style');
        if (!document.getElementById('cielo-loading-animation')) {
            animationStyle.id = 'cielo-loading-animation';
            animationStyle.textContent = `
                @keyframes cielo-pulse {
                    0%, 100% { 
                        opacity: 0.6;
                        transform: scale(1);
                    }
                    50% { 
                        opacity: 1;
                        transform: scale(1.02);
                    }
                }
                
                @keyframes cielo-shimmer {
                    0% {
                        background-position: -200px 0;
                    }
                    100% {
                        background-position: calc(200px + 100%) 0;
                    }
                }
                
                .cielo-payment-info-blocks.loading-skeleton {
                    animation: cielo-pulse 1.5s ease-in-out infinite;
                    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
                    background-size: 200px 100%;
                    animation: cielo-shimmer 1.5s infinite;
                }
                
                .cielo-payment-info-blocks.loading-skeleton .wc-block-components-skeleton__element {
                    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
                    background-size: 200% 100%;
                    animation: cielo-shimmer 1.2s infinite;
                    border-radius: 4px;
                }
            `;
            document.head.appendChild(animationStyle);
        }

        loadingSkeleton.innerHTML = `
            <span class="wc-block-components-totals-item__label">${lknInstallmentLabelTranslations.installment}</span>
            <div class="wc-block-components-totals-item__value">
                <div class="wc-block-components-skeleton__element" aria-live="polite" aria-label="${lknInstallmentLabelTranslations.loadingPrice}" style="width: 80px; height: 1em;"></div>
            </div>
            <div class="wc-block-components-totals-item__description"></div>
        `;

        // Inserir skeleton
        totalDiv.parentNode.insertBefore(loadingSkeleton, totalDiv.nextSibling);
    }

    // Função para inserir informação do Cielo
    function insertCieloInfo() {
        // Procurar todos os componentes Total que ainda não foram processados
        const totalItemDivs = document.querySelectorAll('.wc-block-components-totals-item.wc-block-components-totals-footer-item:not(.cielo-processed)');

        if (totalItemDivs.length === 0) {
            return;
        }

        // Processar cada total encontrado
        totalItemDivs.forEach((totalDiv, index) => {
            // Marcar como processado
            totalDiv.classList.add('cielo-processed');

            // Verificar se já existe informação Cielo (mas não skeleton de loading)
            const existingInfo = totalDiv.parentNode.querySelector('.cielo-payment-info-blocks:not(.loading-skeleton)');
            if (existingInfo) {
                return;
            }

            // Verificar se o método de pagamento selecionado é Cielo
            const cieloSelected = isCieloMethodSelected();
            if (!cieloSelected) {
                return;
            }

            // Obter informações de parcelamento do select
            const installmentInfo = getInstallmentInfo();

            // Se é cartão de débito, não criar nenhuma label
            if (!installmentInfo) {
                return;
            }

            // Se ainda está carregando, inserir skeleton de loading
            if (installmentInfo.isLoading) {
                insertLoadingSkeleton(totalDiv);
                return;
            }

            // Determinar o label baseado na opção selecionada
            let labelText = lknInstallmentLabelTranslations.installment;
            if (installmentInfo.value === '1') {
                labelText = lknInstallmentLabelTranslations.payment;
            }

            // Criar no formato exato do WooCommerce Blocks
            const cieloInfo = document.createElement('div');
            cieloInfo.className = 'wc-block-components-totals-item wc-block-components-totals-footer-item cielo-payment-info-blocks';
            cieloInfo.style.fontSize = 'small';
            cieloInfo.setAttribute('data-installment-value', installmentInfo.value);

            cieloInfo.innerHTML = `
                <span class="wc-block-components-totals-item__label">${labelText}</span>
                <div class="wc-block-components-totals-item__value">
                    <span class="wc-block-formatted-money-amount wc-block-components-formatted-money-amount wc-block-components-totals-footer-item-tax-value">${installmentInfo.text}</span>
                </div>
                <div class="wc-block-components-totals-item__description"></div>
            `;

            // Inserir imediatamente abaixo do componente Total
            totalDiv.parentNode.insertBefore(cieloInfo, totalDiv.nextSibling);
        });
    }

    // Função para remover informação do Cielo
    function removeCieloInfo() {
        const existingInfos = document.querySelectorAll('.cielo-payment-info-blocks');

        existingInfos.forEach(function (existingInfo, index) {
            if (existingInfo && existingInfo.parentNode) {
                existingInfo.remove();
            }
        });

        // Remover a classe de processamento de todos os totais
        const processedTotals = document.querySelectorAll('.wc-block-components-totals-item.wc-block-components-totals-footer-item.cielo-processed');
        processedTotals.forEach(function (total) {
            total.classList.remove('cielo-processed');
        });
    }

    // Função para atualizar parcelamentos existentes (skeletons e elementos finais)
    function updateLoadingSkeletons() {
        // Procurar por skeletons de loading
        const loadingSkeletons = document.querySelectorAll('.cielo-payment-info-blocks.loading-skeleton');

        // Procurar por parcelamentos já existentes (não skeletons)
        const existingParcelamentos = document.querySelectorAll('.cielo-payment-info-blocks:not(.loading-skeleton)');

        const totalElements = loadingSkeletons.length + existingParcelamentos.length;

        if (totalElements > 0) {
            const installmentInfo = getInstallmentInfo();

            // Se installmentInfo é null (débito), remover elementos existentes
            if (!installmentInfo) {
                loadingSkeletons.forEach(function (skeleton) {
                    if (skeleton && skeleton.parentNode) {
                        skeleton.remove();
                    }
                });
                existingParcelamentos.forEach(function (parcelamento) {
                    if (parcelamento && parcelamento.parentNode) {
                        parcelamento.remove();
                    }
                });
                return;
            }

            if (!installmentInfo.isLoading) {
                // Função para atualizar um elemento (skeleton ou parcelamento existente)
                function updateElement(element) {
                    // Determinar o label baseado na opção selecionada
                    let labelText = lknInstallmentLabelTranslations.installment;
                    if (installmentInfo.value === '1') {
                        labelText = lknInstallmentLabelTranslations.payment;
                    }

                    // Atualizar o conteúdo do elemento
                    element.className = 'wc-block-components-totals-item wc-block-components-totals-footer-item cielo-payment-info-blocks';
                    element.setAttribute('data-installment-value', installmentInfo.value);

                    element.innerHTML = `
                        <span class="wc-block-components-totals-item__label">${labelText}</span>
                        <div class="wc-block-components-totals-item__value">
                            <span class="wc-block-formatted-money-amount wc-block-components-formatted-money-amount wc-block-components-totals-footer-item-tax-value">${installmentInfo.text}</span>
                        </div>
                        <div class="wc-block-components-totals-item__description"></div>
                    `;
                }

                // Atualizar todos os skeletons
                loadingSkeletons.forEach(function (skeleton) {
                    updateElement(skeleton);
                });

                // Atualizar todos os parcelamentos existentes
                existingParcelamentos.forEach(function (parcelamento) {
                    updateElement(parcelamento);
                });
            }
        }
    }

    // Função para ativar skeleton de loading quando detecta estado de carregamento
    function activateLoadingSkeleton() {
        // Verificar se método Cielo está selecionado
        if (!isCieloMethodSelected()) {
            return;
        }

        // Buscar parcelamentos existentes que não são skeletons
        const existingParcelamentos = document.querySelectorAll('.cielo-payment-info-blocks:not(.loading-skeleton)');

        if (existingParcelamentos.length > 0) {
            // Converter parcelamentos existentes em skeletons
            existingParcelamentos.forEach(function (parcelamento) {
                // Adicionar classe de skeleton
                parcelamento.classList.add('loading-skeleton');

                // Atualizar conteúdo para skeleton
                parcelamento.innerHTML = `
                    <span class="wc-block-components-totals-item__label">${lknInstallmentLabelTranslations.installment}</span>
                    <div class="wc-block-components-totals-item__value">
                        <div class="wc-block-components-skeleton__element" aria-live="polite" aria-label="${lknInstallmentLabelTranslations.loadingPrice}" style="width: 80px; height: 1em;"></div>
                    </div>
                    <div class="wc-block-components-totals-item__description"></div>
                `;
            });
        } else {
            // Criar novo skeleton se não existe nenhum parcelamento
            const totalComponents = document.querySelectorAll('.wc-block-components-totals-item.wc-block-components-totals-footer-item:not(.cielo-processed)');

            if (totalComponents.length > 0) {
                totalComponents.forEach(function (totalComponent) {
                    insertLoadingSkeleton(totalComponent);
                    totalComponent.classList.add('cielo-processed');
                });
            }
        }
    }

    // Função para observar mudanças no tipo de cartão
    function observeCardTypeSelects() {
        const cardTypeSelect = document.querySelector('.lkn-credit-debit-card-type-select select');
        
        if (cardTypeSelect && !cardTypeSelect.dataset.cardTypeObserverAdded) {
            cardTypeSelect.addEventListener('change', function() {
                // Remover informações existentes e recriar
                removeCieloInfo();
                setTimeout(() => {
                    insertCieloInfo();
                }, 100);
            });
            
            cardTypeSelect.dataset.cardTypeObserverAdded = 'true';
        } else if (cardTypeSelect) {
            // Observer já existe
        }
    }

    // Função para observar mudanças nos selects de parcelamento
    function observeInstallmentSelects() {
        const cieloSelects = document.querySelectorAll('.lkn_cielo_credit_select select, .lkn_cielo_debit_select select');

        cieloSelects.forEach(function (select, index) {
            // Verificar se já tem observer
            if (select.dataset.observerAdded) {
                return;
            }

            let lastValue = null;
            let lastText = null;
            let observerTimeout = null;
            let checkCount = 0;
            const maxChecks = 30; // Máximo 15 segundos de observação (500ms * 30)

            // Função para verificar e atualizar se necessário
            function checkAndUpdate() {
                checkCount++;
                
                const installmentInfo = getInstallmentInfo();
                
                // Se installmentInfo é null (débito), parar observação
                if (!installmentInfo) {
                    return;
                }
                
                const currentValue = installmentInfo.value;
                const currentText = installmentInfo.text;

                // Verificar se houve mudança significativa
                if (currentValue !== lastValue || currentText !== lastText) {
                    lastValue = currentValue;
                    lastText = currentText;

                    // Verificar se entrou em estado de loading
                    if (installmentInfo.isLoading && (currentValue === 'loading' || currentText.includes('🔄') ||
                        currentText.includes('Calculando') || currentText.includes('Calculating'))) {
                        activateLoadingSkeleton();
                    }
                    // Verificar se saiu do estado de loading
                    else if (!installmentInfo.isLoading && currentValue !== 'loading') {
                        updateLoadingSkeletons();
                    }
                }

                // Continuar observando se ainda não atingiu o máximo
                if (checkCount < maxChecks) {
                    // Se ainda está carregando ou mudou recentemente, continua observando
                    if (installmentInfo.isLoading || checkCount <= 5) {
                        observerTimeout = setTimeout(checkAndUpdate, 500);
                    }
                }
            }

            // Observer para mudanças no select (gatilho inicial)
            const selectObserver = new MutationObserver(function (mutations) {
                let hasChanges = false;

                mutations.forEach(function (mutation) {
                    if (mutation.type === 'childList' || mutation.type === 'characterData') {
                        hasChanges = true;
                    }
                });

                if (hasChanges) {
                    // Resetar contador e iniciar observação contínua
                    checkCount = 0;

                    // Cancelar timeout anterior se existir
                    if (observerTimeout) {
                        clearTimeout(observerTimeout);
                    }

                    // Iniciar nova sessão de observação
                    checkAndUpdate();
                }
            });

            // Observar mudanças no select
            selectObserver.observe(select, {
                childList: true,
                subtree: true,
                characterData: true
            });

            // Marcar como tendo observer
            select.dataset.observerAdded = 'true';

            // Listener de change como gatilho adicional
            select.addEventListener('change', function () {
                // Resetar e iniciar nova observação
                checkCount = 0;
                if (observerTimeout) {
                    clearTimeout(observerTimeout);
                }
                checkAndUpdate();
            });
        });
    }

    // Função otimizada para verificar método de pagamento
    function checkPaymentMethod() {
        const checkedInput = document.querySelector('input[name="radio-control-wc-payment-method-options"]:checked');
        const selectedMethod = checkedInput ? checkedInput.value : null;

        // Processar sempre que for método Cielo
        if (selectedMethod === 'lkn_cielo_credit' || selectedMethod === 'lkn_cielo_debit') {
            // Se mudou de método, limpar e forçar recriação
            if (selectedMethod !== lastSelectedMethod) {
                removeCieloInfo();
                // Forçar recriação removendo classe de processado
                setTimeout(() => {
                    const processedTotals = document.querySelectorAll('.cielo-processed');
                    processedTotals.forEach(total => total.classList.remove('cielo-processed'));
                    insertCieloInfo();
                }, 150);
            } else {
                // Mesmo método, apenas atualizar
                insertCieloInfo();
                updateLoadingSkeletons();
            }

            // Inicializar observação dos selects de parcelamento
            setTimeout(() => {
                observeInstallmentSelects();
                observeCardTypeSelects();
            }, 500);

            lastSelectedMethod = selectedMethod;
        } else if (selectedMethod !== lastSelectedMethod) {
            removeCieloInfo();
            lastSelectedMethod = selectedMethod;
        }
    }

    // Função para inicializar listeners
    function initializePaymentListeners() {
        const paymentInputs = document.querySelectorAll('input[name="radio-control-wc-payment-method-options"]');

        if (paymentInputs.length > 0 && !isInitialized) {
            paymentInputs.forEach(function (input) {
                input.addEventListener('change', checkPaymentMethod);
            });

            isInitialized = true;
        }

        // Verificação inicial
        checkPaymentMethod();
    }

    // Observer contínuo - detecta adições E remoções
    const observer = new MutationObserver(function (mutations) {
        let shouldCheckPayments = false;
        let shouldCheckTotals = false;
        let shouldCheckInstallments = false;

        for (let mutation of mutations) {
            if (mutation.type === 'childList') {
                // Verificar ADIÇÕES
                for (let node of mutation.addedNodes) {
                    if (node.nodeType === 1) {
                        // Verificar se novos elementos de pagamento foram adicionados
                        if ((node.querySelector && node.querySelector('input[name="radio-control-wc-payment-method-options"]')) ||
                            (node.name && node.name === 'radio-control-wc-payment-method-options')) {
                            shouldCheckPayments = true;
                        }

                        // Verificar se novos componentes de total foram adicionados
                        if ((node.classList && node.classList.contains('wc-block-components-totals-item')) ||
                            (node.querySelector && node.querySelector('.wc-block-components-totals-item'))) {
                            shouldCheckTotals = true;
                        }

                        // Verificar se novos selects de parcelamento Cielo foram adicionados/modificados
                        if ((node.classList && (node.classList.contains('lkn_cielo_credit_select') || node.classList.contains('lkn_cielo_debit_select'))) ||
                            (node.querySelector && (node.querySelector('.lkn_cielo_credit_select') || node.querySelector('.lkn_cielo_debit_select'))) ||
                            (node.querySelector && node.querySelector('select[id*="wc-block-components-sort-select"]'))) {
                            shouldCheckInstallments = true;
                        }

                        // Verificar se é um select específico de parcelamento
                        if (node.tagName === 'SELECT' && (node.classList.contains('wc-block-sort-select__select') || node.id.includes('sort-select'))) {
                            shouldCheckInstallments = true;
                        }
                    }
                }
            }
            
            // Verificar mudanças de atributos em selects existentes
            if (mutation.type === 'attributes' && mutation.target.tagName === 'SELECT') {
                const target = mutation.target;
                if (target.classList.contains('wc-block-sort-select__select') || target.id.includes('sort-select') ||
                    target.closest('.lkn_cielo_credit_select') || target.closest('.lkn_cielo_debit_select')) {
                    shouldCheckInstallments = true;
                }
            }
            
            // Verificar mudanças de caracterData em options
            if (mutation.type === 'characterData' && mutation.target.parentNode && mutation.target.parentNode.tagName === 'OPTION') {
                const selectParent = mutation.target.parentNode.parentNode;
                if (selectParent && selectParent.tagName === 'SELECT' && 
                    (selectParent.classList.contains('wc-block-sort-select__select') || selectParent.id.includes('sort-select') ||
                     selectParent.closest('.lkn_cielo_credit_select') || selectParent.closest('.lkn_cielo_debit_select'))) {
                    shouldCheckInstallments = true;
                }
            }
        }

        // Reinicializar listeners quando novos elementos aparecem
        if (shouldCheckPayments) {
            initializePaymentListeners();
        }

        // Verificar totals quando detectados
        if (shouldCheckTotals) {
            setTimeout(() => {
                checkPaymentMethod();
            }, 300);
        }
        
        // Verificar parcelamentos quando detectados
        if (shouldCheckInstallments) {
            setTimeout(() => {
                if (isCieloMethodSelected()) {
                    updateLoadingSkeletons();
                    // Reinicializar observers se necessário
                    setTimeout(() => {
                        observeInstallmentSelects();
                    }, 100);
                }
            }, 200);
        }
    });

    // Observar o checkout
    const checkoutArea = document.querySelector('.wc-block-checkout') || document.body;

    observer.observe(checkoutArea, {
        childList: true,
        subtree: true
    });

    // Inicialização
    initializePaymentListeners();
});