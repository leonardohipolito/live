function initLiveModel(el){
	updateLiveModelInputs(el)
	let data = el.__live.data
	el.addEventListener('change', e => {
		if (!e.target.hasAttribute('live:model')) return;
		let property = e.target.getAttribute('live:model')
		let value = e.target.value
		sendRequest(el,{updateProperty: [property,value]})
	});
}
function updateLiveModelInputs(element){
	let data = element.__live.data
	document.querySelectorAll('[live\\:model]').forEach(el => {
		let property = el.getAttribute('live:model')
		el.value = element.__live.data[property]
	})
}
function initSlimClick(el){
	el.addEventListener('click', e => {
			if (!e.target.hasAttribute('live:click')) return;
			let method = e.target.getAttribute('live:click')
			sendRequest(el,{callMethod: method})
		})
}
function sendRequest(el,addToPayload){
	fetch('/live', {
		method: 'POST',
		headers: {
			'Content-Type': 'application/json',
		},
		body: JSON.stringify({
			snapshot:el.__live,
			...addToPayload
		})
	}).then(res => res.json()).then(res => {
		el.__live = res.snapshot
		console.log(res.html)
		el.innerHTML = res.html
		Alpine.morph(el.firstElementChild,res.html)
		// updateLiveModelInputs(el);
	})
}
document.addEventListener("DOMContentLoaded", () => {
	console.log('inicializado')
	document.querySelectorAll('[live\\:snapshot]').forEach(el => {
		el.__live = JSON.parse(el.getAttribute('live:snapshot'))
		initSlimClick(el)
		initLiveModel(el)
	})
})