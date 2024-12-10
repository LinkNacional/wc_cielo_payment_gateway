(function ($) {
    $(window).load(function () {
        // Selecionar os elementos
        var lknWcCieloCreditBlocksSettingsLayoutMenuVar = 1;
        const mainForm = document.querySelector('#mainform');
        const fistH1 = mainForm.querySelector('h1');
        const submitP = mainForm.querySelector('p.submit');
        const tables = mainForm.querySelectorAll('table');

        if(mainform && fistH1 && submitP && tables){
            // Criar uma nova div
            const newDiv = document.createElement('div');
            newDiv.id = 'lknWcCieloCreditBlocksSettingsLayoutDiv';
    
            // Acessar o próximo elemento após fistH1
            let currentElement = fistH1; // Começar com fistH1
    
            // Mover fistH1 e todos os elementos entre fistH1 e submitP para a nova div
            while (currentElement && currentElement !== submitP.nextElementSibling) {
                const nextElement = currentElement.nextElementSibling; // Armazenar o próximo elemento antes de mover
                newDiv.appendChild(currentElement); // Mover o elemento atual para a nova div
                currentElement = nextElement; // Atualizar currentElement para o próximo
            }
    
            // Mover submitP para a nova div
            newDiv.appendChild(submitP);
    
            // Adicionar a nova div ao mainForm
            mainForm.appendChild(newDiv);
    
            let subTitles = mainForm.querySelectorAll('.wc-settings-sub-title');
            let descriptionElement = mainForm.querySelector('p');
            let divElement = document.createElement('div');
            if(subTitles && descriptionElement){
                // Criar a div que irá conter os novos elementos <p>
                divElement.id = 'lknWcCieloCreditBlocksSettingsLayoutMenu';
                aElements = [];
                subTitles.forEach((subTitle, index) => {
                    
                    // Criar um novo elemento <a> e adicionar o elemento <p> a ele
                    let aElement = document.createElement('a');
                    aElement.textContent = subTitle.textContent;
                    aElement.href = '#' + subTitle.textContent;
                    aElement.className = 'nav-tab';
                    aElement.onclick = (event) => {
                        lknWcCieloCreditBlocksSettingsLayoutMenuVar = index + 1;
                        aElements.forEach((pElement, indexP) => {
                            if(indexP == index){
                                aElements[index].className = 'nav-tab nav-tab-active';
                            }else{
                                aElements[indexP].className = 'nav-tab';
                            }
                        });
                        changeLayout()
                    }
        
                    // Adicionar o novo elemento <a> à div
                    divElement.appendChild(aElement);
                    aElements.push(aElement);
        
                    // Remover o subtítulo original
                    subTitle.parentNode.removeChild(subTitle);
                });
                
                aElements[0].className = 'nav-tab nav-tab-active';

                // Inserir a div após o segundo ou primeiro <p>
                const pElements = mainForm.querySelectorAll('p:not([class])');
                let nodeArray = Array.from(pElements);
                let lastNode = nodeArray[nodeArray.length - 1];
                if(lastNode){
                    lastNode.parentNode.insertBefore(divElement, lastNode.nextSibling);
                }
        
                tables.forEach((table, index) => {
                    if(index != 0 && index != 1) {
                        table.style.display = 'none';
                    }
                    table.menuIndex = index;
                });
        
        
                function changeLayout(){
                    tables.forEach((table, index) => {
                        switch(lknWcCieloCreditBlocksSettingsLayoutMenuVar){
                            case 1:
                                if(index == 0 || index == 1) {
                                    table.style.display = 'block';
                                }else{
                                    table.style.display = 'none';
                                }
                            break;
                            case 2:
                                if(index == 2) {
                                    table.style.display = 'block';
                                }else{
                                    table.style.display = 'none';
                                }
                            break
                            case 3:
                                if(index == 3) {
                                    table.style.display = 'block';
                                }else{
                                    table.style.display = 'none';
                                }
                            break
                            case 4:
                                if(index == 4) {
                                    table.style.display = 'block';
                                }else{
                                    table.style.display = 'none';
                                }
                            break
                        }
                    });
                    
                }

                //Caso o formulário tenha um campo inválido, força o click no menu em que o campo inválido está
                mainForm.addEventListener('invalid', function (event) {
                    const invalidField = event.target;
                    if (invalidField) {
                        let parentNode = invalidField.parentNode;
                        while (parentNode && parentNode.tagName !== 'TABLE') {
                            parentNode = parentNode.parentNode;
                        }
                        if (parentNode) {
                            //Força o click no menu em que o campo inválido está
                            aElements[parentNode.menuIndex-1].click()
                        }
                    }
                }, true);

                const urlHash = window.location.hash;
                if (urlHash) {
                    const targetElement = aElements.find(a => a.href.endsWith(urlHash));
                    if (targetElement) {
                        targetElement.click();
                    }
                }
            }

            let hrElement = document.createElement('hr');
            divElement.parentElement.insertBefore(hrElement, divElement.nextSibling);
        }
    });
})(jQuery);
