const DELETE = document.querySelector('.button_1');
DELETE.addEventListener('click', popOpen1);

function popOpen1() {
	alert('삭제 시 복구불가합니다.\n삭제하시겠습니까?')
	removeEventListener('click', popOpen1);
}