/**
 * Script otimizado para testar hooks do WooCommerce Blocks - Cielo Payment Gateway
 */
document.addEventListener('DOMContentLoaded', function () {
    console.log('Cielo Blocks Test Script: DOM loaded');

    let isInitialized = false;
    let lastSelectedMethod = null;
    let lastInstallmentValue = null;

    // Função para verificar se método Cielo está selecionado
    function isCieloMethodSelected() {
        console.log('isCieloMethodSelected: Verificando método de pagamento selecionado');

        // Verificar se há radios de pagamento selecionados
        const selectedPaymentRadio = document.querySelector('input[name="radio-control-wc-payment-method-options"]:checked');

        if (selectedPaymentRadio) {
            const selectedMethod = selectedPaymentRadio.value;
            console.log('isCieloMethodSelected: Método selecionado:', selectedMethod);

            // Verificar se é um método Cielo (credit ou debit)
            const isCielo = selectedMethod === 'lkn_cielo_credit' || selectedMethod === 'lkn_cielo_debit';
            console.log('isCieloMethodSelected: É método Cielo:', isCielo);
            return isCielo;
        }

        console.log('isCieloMethodSelected: Nenhum método selecionado');
        return false;
    }

    // Função para obter informações de parcelamento do select
    function getInstallmentInfo() {
        console.log('getInstallmentInfo: Buscando informações de parcelamento');

        // Buscar pelas divs dos métodos Cielo
        const cieloSelects = document.querySelectorAll('.lkn_cielo_credit_select, .lkn_cielo_debit_select');

        for (let cieloDiv of cieloSelects) {
            // Verificar se está carregando (skeleton)
            const skeleton = cieloDiv.querySelector('.wc-block-components-skeleton__element');
            if (skeleton) {
                console.log('getInstallmentInfo: Select ainda carregando (skeleton detectado)');
                return { text: 'Carregando...', isLoading: true };
            }

            // Buscar o select dentro da div
            const select = cieloDiv.querySelector('select');
            if (select) {
                const selectedOption = select.options[select.selectedIndex];
                if (selectedOption) {
                    const optionText = selectedOption.textContent || selectedOption.innerText;
                    const selectedValue = selectedOption.value;

                    console.log('getInstallmentInfo: Opção selecionada:', optionText);
                    console.log('getInstallmentInfo: Valor selecionado:', selectedValue);

                    // Verificar se ainda está com texto de loading
                    if (optionText.includes('Calculando parcelas') || optionText.includes('🔄') || selectedValue === 'loading') {
                        console.log('getInstallmentInfo: Select ainda calculando parcelas');
                        return { text: 'Calculando parcelas...', isLoading: true };
                    }

                    // Extrair apenas a parte do parcelamento, removendo juros/descontos
                    let cleanText = optionText
                        .replace(/\s*\(.*?\)\s*/g, '') // Remove tudo entre parênteses
                        .replace(/\s*sem\s+juros\s*/gi, '') // Remove "sem juros"
                        .replace(/\s*à\s+vista\s*/gi, '') // Remove "à vista"
                        .replace(/&nbsp;/g, ' ') // Replace HTML space
                        .replace(/🔄/g, '') // Remove emoji de loading
                        .trim();

                    // Se for valor 1, mostrar como "À vista"
                    if (selectedValue === '1') {
                        return { text: 'À vista', isLoading: false, value: selectedValue };
                    } else {
                        return { text: cleanText, isLoading: false, value: selectedValue };
                    }
                }
            }
        }

        console.log('getInstallmentInfo: Select de parcelamento não encontrado');
        return { text: '2x de R$ 15,00', isLoading: false, value: '2' }; // fallback
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
            <span class="wc-block-components-totals-item__label">Parcelamento</span>
            <div class="wc-block-components-totals-item__value">
                <div class="wc-block-components-skeleton__element" aria-live="polite" aria-label="Loading price..." style="width: 80px; height: 1em;"></div>
            </div>
            <div class="wc-block-components-totals-item__description"></div>
        `;

        // Inserir skeleton
        totalDiv.parentNode.insertBefore(loadingSkeleton, totalDiv.nextSibling);
        console.log('insertLoadingSkeleton: Skeleton de loading inserido');
    }

    // Função para inserir informação do Cielo
    function insertCieloInfo() {
        console.log('insertCieloInfo: Iniciando busca por componentes Total');

        // Verificar se há componentes Total na página primeiro
        const allTotals = document.querySelectorAll('.wc-block-components-totals-item.wc-block-components-totals-footer-item');
        console.log('insertCieloInfo: Total de componentes Total na página:', allTotals.length);

        // Procurar todos os componentes Total que ainda não foram processados
        const totalItemDivs = document.querySelectorAll('.wc-block-components-totals-item.wc-block-components-totals-footer-item:not(.cielo-processed)');
        console.log('insertCieloInfo: Encontrados ' + totalItemDivs.length + ' componentes Total não processados');

        // Verificar se há parcelamentos já existentes
        const existingParcelamentos = document.querySelectorAll('.cielo-payment-info-blocks');
        console.log('insertCieloInfo: Parcelamentos já existentes:', existingParcelamentos.length);

        if (totalItemDivs.length === 0) {
            console.log('insertCieloInfo: Nenhum componente Total novo encontrado');
            return;
        }

        // Processar cada total encontrado
        totalItemDivs.forEach((totalDiv, index) => {
            console.log(`insertCieloInfo: Processando total ${index + 1} de ${totalItemDivs.length}`);
            console.log(`insertCieloInfo: Elemento total ${index + 1}:`, totalDiv);

            // Marcar como processado
            totalDiv.classList.add('cielo-processed');
            console.log(`insertCieloInfo: Total ${index + 1} marcado como processado`);

            // Verificar se já existe informação Cielo (mas não skeleton de loading)
            const existingInfo = totalDiv.parentNode.querySelector('.cielo-payment-info-blocks:not(.loading-skeleton)');
            if (existingInfo) {
                console.log(`insertCieloInfo: Total ${index + 1} já possui informação Cielo final, pulando`);
                return;
            }

            // Verificar se o método de pagamento selecionado é Cielo
            const cieloSelected = isCieloMethodSelected();
            console.log(`insertCieloInfo: Método Cielo selecionado para total ${index + 1}:`, cieloSelected);
            if (!cieloSelected) {
                console.log(`insertCieloInfo: Método Cielo não selecionado para total ${index + 1}, pulando`);
                return;
            }

            // Obter informações de parcelamento do select
            const installmentInfo = getInstallmentInfo();
            let installmentText = installmentInfo.text;

            console.log('insertCieloInfo: Texto de parcelamento obtido:', installmentText);

            // Se ainda está carregando, inserir skeleton de loading
            if (installmentInfo.isLoading) {
                console.log('insertCieloInfo: Select carregando, inserindo skeleton...');
                insertLoadingSkeleton(totalDiv);
                return;
            }

            // Determinar o label baseado na opção selecionada
            let labelText = 'Parcelamento';
            if (installmentInfo.value === '1') {
                labelText = 'Pagamento';
            }

            // Criar no formato exato do WooCommerce Blocks
            const cieloInfo = document.createElement('div');
            cieloInfo.className = 'wc-block-components-totals-item wc-block-components-totals-footer-item cielo-payment-info-blocks';
            cieloInfo.style.fontSize = 'small';
            cieloInfo.setAttribute('data-installment-value', installmentInfo.value); // Para tracking

            cieloInfo.innerHTML = `
                <span class="wc-block-components-totals-item__label">${labelText}</span>
                <div class="wc-block-components-totals-item__value">
                    <span class="wc-block-formatted-money-amount wc-block-components-formatted-money-amount wc-block-components-totals-footer-item-tax-value">${installmentText}</span>
                </div>
                <div class="wc-block-components-totals-item__description"></div>
            `;

            // Inserir imediatamente abaixo do componente Total
            totalDiv.parentNode.insertBefore(cieloInfo, totalDiv.nextSibling);
            console.log('insertCieloInfo: Parcelamento inserido abaixo do componente Total - ' + installmentText);
        });
    }

    // Função para remover informação do Cielo
    function removeCieloInfo() {
        console.log('removeCieloInfo: Iniciando remoção de parcelamentos');

        const existingInfos = document.querySelectorAll('.cielo-payment-info-blocks');
        console.log('removeCieloInfo: Encontrados ' + existingInfos.length + ' parcelamentos para remover');

        existingInfos.forEach(function (existingInfo) {
            existingInfo.style.animation = 'fadeOut 0.3s ease-out';
            setTimeout(() => {
                if (existingInfo && existingInfo.parentNode) {
                    existingInfo.remove();
                    console.log('removeCieloInfo: Parcelamento removido');
                }
            }, 300);
        });

        // Remover a classe de processamento de todos os totais
        const processedTotals = document.querySelectorAll('.wc-block-components-totals-item.wc-block-components-totals-footer-item.cielo-processed');
        processedTotals.forEach(function (total) {
            total.classList.remove('cielo-processed');
        });

        console.log('removeCieloInfo: Todas as marcas de processamento limpas');
    }

    // Função para atualizar parcelamentos existentes (skeletons e elementos finais)
    function updateLoadingSkeletons() {
        console.log('updateLoadingSkeletons: Verificando parcelamentos existentes');

        // Procurar por skeletons de loading
        const loadingSkeletons = document.querySelectorAll('.cielo-payment-info-blocks.loading-skeleton');
        console.log('updateLoadingSkeletons: Encontrados', loadingSkeletons.length, 'skeletons de loading');

        // Procurar por parcelamentos já existentes (não skeletons)
        const existingParcelamentos = document.querySelectorAll('.cielo-payment-info-blocks:not(.loading-skeleton)');
        console.log('updateLoadingSkeletons: Encontrados', existingParcelamentos.length, 'parcelamentos existentes');

        const totalElements = loadingSkeletons.length + existingParcelamentos.length;

        if (totalElements > 0) {
            const installmentInfo = getInstallmentInfo();
            console.log('updateLoadingSkeletons: Info de parcelamento:', installmentInfo);

            if (!installmentInfo.isLoading) {
                console.log('updateLoadingSkeletons: Select carregado, atualizando elementos...');

                // Função para atualizar um elemento (skeleton ou parcelamento existente)
                function updateElement(element, elementType) {
                    // Determinar o label baseado na opção selecionada
                    let labelText = 'Parcelamento';
                    if (installmentInfo.value === '1') {
                        labelText = 'Pagamento';
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

                    console.log(`updateLoadingSkeletons: ${elementType} atualizado para:`, installmentInfo.text);
                }

                // Atualizar todos os skeletons
                loadingSkeletons.forEach(function (skeleton) {
                    updateElement(skeleton, 'Skeleton');
                });

                // Atualizar todos os parcelamentos existentes
                existingParcelamentos.forEach(function (parcelamento) {
                    updateElement(parcelamento, 'Parcelamento existente');
                });
            } else {
                console.log('updateLoadingSkeletons: Select ainda carregando, mantendo skeletons');
            }
        }
    }

    // Função para ativar skeleton de loading quando detecta estado de carregamento
    function activateLoadingSkeleton() {
        console.log('activateLoadingSkeleton: Ativando skeleton de loading');

        // Verificar se método Cielo está selecionado
        if (!isCieloMethodSelected()) {
            console.log('activateLoadingSkeleton: Método Cielo não selecionado, ignorando');
            return;
        }

        // Buscar parcelamentos existentes que não são skeletons
        const existingParcelamentos = document.querySelectorAll('.cielo-payment-info-blocks:not(.loading-skeleton)');
        console.log('activateLoadingSkeleton: Encontrados', existingParcelamentos.length, 'parcelamentos para converter em skeleton');

        if (existingParcelamentos.length > 0) {
            // Converter parcelamentos existentes em skeletons
            existingParcelamentos.forEach(function (parcelamento) {
                console.log('activateLoadingSkeleton: Convertendo parcelamento existente em skeleton');

                // Adicionar classe de skeleton
                parcelamento.classList.add('loading-skeleton');

                // Atualizar conteúdo para skeleton
                parcelamento.innerHTML = `
                    <span class="wc-block-components-totals-item__label">Parcelamento</span>
                    <div class="wc-block-components-totals-item__value">
                        <div class="wc-block-components-skeleton__element" aria-live="polite" aria-label="Loading price..." style="width: 80px; height: 1em;"></div>
                    </div>
                    <div class="wc-block-components-totals-item__description"></div>
                `;

                console.log('activateLoadingSkeleton: Parcelamento convertido em skeleton');
            });
        } else {
            // Criar novo skeleton se não existe nenhum parcelamento
            console.log('activateLoadingSkeleton: Nenhum parcelamento existente, criando novo skeleton');

            const totalComponents = document.querySelectorAll('.wc-block-components-totals-item.wc-block-components-totals-footer-item:not(.cielo-processed)');

            if (totalComponents.length > 0) {
                totalComponents.forEach(function (totalComponent) {
                    insertLoadingSkeleton(totalComponent);
                    totalComponent.classList.add('cielo-processed');
                });
            }
        }
    }

    // Função para observar mudanças nos selects de parcelamento
    function observeInstallmentSelects() {
        console.log('observeInstallmentSelects: Iniciando observação dos selects');

        const cieloSelects = document.querySelectorAll('.lkn_cielo_credit_select select, .lkn_cielo_debit_select select');
        console.log('observeInstallmentSelects: Encontrados', cieloSelects.length, 'selects de parcelamento');

        cieloSelects.forEach(function (select, index) {
            // Verificar se já tem observer
            if (select.dataset.observerAdded) {
                console.log(`observeInstallmentSelects: Select ${index + 1} já tem observer`);
                return;
            }

            console.log(`observeInstallmentSelects: Adicionando observer contínuo ao select ${index + 1}`);

            let lastValue = null;
            let lastText = null;
            let observerTimeout = null;
            let checkCount = 0;
            const maxChecks = 30; // Máximo 15 segundos de observação (500ms * 30)

            // Função para verificar e atualizar se necessário
            function checkAndUpdate() {
                checkCount++;
                const installmentInfo = getInstallmentInfo();
                const currentValue = installmentInfo.value;
                const currentText = installmentInfo.text;

                console.log(`observeInstallmentSelects: Check ${checkCount}/${maxChecks} - Select ${index + 1}:`, installmentInfo);

                // Verificar se houve mudança significativa
                if (currentValue !== lastValue || currentText !== lastText) {
                    console.log(`observeInstallmentSelects: Mudança detectada no select ${index + 1}`);
                    console.log(`observeInstallmentSelects: Anterior: value="${lastValue}", text="${lastText}"`);
                    console.log(`observeInstallmentSelects: Atual: value="${currentValue}", text="${currentText}"`);

                    lastValue = currentValue;
                    lastText = currentText;

                    // Verificar se entrou em estado de loading
                    if (installmentInfo.isLoading && (currentValue === 'loading' || currentText.includes('🔄') || currentText.includes('Calculando'))) {
                        console.log(`observeInstallmentSelects: Select ${index + 1} entrou em loading, ativando skeleton`);
                        activateLoadingSkeleton();
                    }
                    // Verificar se saiu do estado de loading
                    else if (!installmentInfo.isLoading && currentValue !== 'loading') {
                        console.log(`observeInstallmentSelects: Select ${index + 1} carregado, atualizando parcelamentos`);
                        updateLoadingSkeletons();
                    }
                }

                // Continuar observando se ainda não atingiu o máximo
                if (checkCount < maxChecks) {
                    // Se ainda está carregando ou mudou recentemente, continua observando
                    if (installmentInfo.isLoading || checkCount <= 5) {
                        observerTimeout = setTimeout(checkAndUpdate, 500);
                    } else {
                        console.log(`observeInstallmentSelects: Select ${index + 1} estabilizado, parando observação temporária`);
                    }
                } else {
                    console.log(`observeInstallmentSelects: Select ${index + 1} atingiu máximo de verificações`);
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
                    console.log(`observeInstallmentSelects: MutationObserver ativado para select ${index + 1}`);

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
                console.log(`observeInstallmentSelects: Change event no select ${index + 1} - iniciando observação`);

                // Resetar e iniciar nova observação
                checkCount = 0;
                if (observerTimeout) {
                    clearTimeout(observerTimeout);
                }
                checkAndUpdate();
            });

            console.log(`observeInstallmentSelects: Observer completo configurado para select ${index + 1}`);
        });
    }

    // Função otimizada para verificar método de pagamento
    function checkPaymentMethod() {
        console.log('checkPaymentMethod: Verificando método de pagamento');
        const checkedInput = document.querySelector('input[name="radio-control-wc-payment-method-options"]:checked');
        const selectedMethod = checkedInput ? checkedInput.value : null;

        console.log('checkPaymentMethod: Método atual detectado:', selectedMethod);
        console.log('checkPaymentMethod: Último método:', lastSelectedMethod);

        // Processar sempre que for método Cielo, mesmo se não mudou (para capturar novos totals)
        if (selectedMethod === 'lkn_cielo_credit' || selectedMethod === 'lkn_cielo_debit') {
            console.log('checkPaymentMethod: Método Cielo detectado, inserindo info');
            insertCieloInfo();

            // Verificar se há skeletons de loading para substituir
            updateLoadingSkeletons();

            // Inicializar observação dos selects de parcelamento
            setTimeout(() => {
                observeInstallmentSelects();
            }, 500); // Delay para garantir que os selects estejam carregados

            lastSelectedMethod = selectedMethod;
        } else if (selectedMethod !== lastSelectedMethod) {
            console.log('checkPaymentMethod: Método mudou para não-Cielo, removendo info');
            removeCieloInfo();
            lastSelectedMethod = selectedMethod;
        }
    }

    // Função para inicializar listeners
    function initializePaymentListeners() {
        console.log('initializePaymentListeners: Adicionando listeners de pagamento');

        const paymentInputs = document.querySelectorAll('input[name="radio-control-wc-payment-method-options"]');
        console.log('initializePaymentListeners: Encontrados', paymentInputs.length, 'inputs de pagamento');

        if (paymentInputs.length > 0 && !isInitialized) {
            paymentInputs.forEach(function (input) {
                console.log('initializePaymentListeners: Adicionando listener para', input.value);
                input.addEventListener('change', checkPaymentMethod);
            });

            isInitialized = true;
            console.log('initializePaymentListeners: Payment listeners initialized');
        } else if (isInitialized) {
            console.log('initializePaymentListeners: Já inicializado, pulando...');
        }

        // Verificação inicial
        console.log('initializePaymentListeners: Executando verificação inicial...');
        checkPaymentMethod();
    }

    // Observer contínuo - detecta adições E remoções
    const observer = new MutationObserver(function (mutations) {
        let shouldCheckPayments = false;
        let shouldCheckTotals = false;

        for (let mutation of mutations) {
            if (mutation.type === 'childList') {
                // Verificar ADIÇÕES
                for (let node of mutation.addedNodes) {
                    if (node.nodeType === 1) {
                        // Verificar se novos elementos de pagamento foram adicionados
                        if ((node.querySelector && node.querySelector('input[name="radio-control-wc-payment-method-options"]')) ||
                            (node.name && node.name === 'radio-control-wc-payment-method-options')) {
                            shouldCheckPayments = true;
                            console.log('Observer: Detectou novo elemento de pagamento');
                        }

                        // Verificar se novos componentes de total foram adicionados
                        if ((node.classList && node.classList.contains('wc-block-components-totals-item')) ||
                            (node.querySelector && node.querySelector('.wc-block-components-totals-item'))) {
                            shouldCheckTotals = true;
                            console.log('Observer: Detectou novo componente totals');
                        }
                    }
                }
            }
        }

        // Reinicializar listeners quando novos elementos aparecem
        if (shouldCheckPayments) {
            console.log('Observer: Reinicializando listeners');
            initializePaymentListeners();
        }

        // Verificar totals quando detectados
        if (shouldCheckTotals) {
            console.log('Observer: Mudanças nos totals detectadas');
            setTimeout(() => {
                checkPaymentMethod();
            }, 300);
        }
    });

    // Observar o checkout
    const checkoutArea = document.querySelector('.wc-block-checkout') || document.body;
    console.log('Observer: Observando área do checkout');

    observer.observe(checkoutArea, {
        childList: true,
        subtree: true
    });

    // Inicialização
    console.log('Cielo Blocks Test: Iniciando listeners...');
    initializePaymentListeners();

    console.log('Cielo Blocks Test: Optimized script initialized');
});