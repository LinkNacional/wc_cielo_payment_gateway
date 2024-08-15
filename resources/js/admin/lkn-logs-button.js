const postboxContent = document.querySelector('#postbox-container-1')
const myPostboxContent = document.querySelector('#lkn-btn-postbox') // Selecionando pelo ID

if (postboxContent && myPostboxContent) {
  postboxContent.appendChild(myPostboxContent)
} else {
  console.warn('Um ou ambos os elementos não foram encontrados.')
}
