novaConta = function() {
	axios.post('/v1/bankaccounts', {
	    name: document.getElementById('novaConta'),
	    balance: document.getElementById('valorConta')
	  })
	  .then(function (response) {
	    console.log(response);
	  })
	  .catch(function (error) {
	    console.log(error);
	});
};

$(document).ready(function(){
	$('.mask-money').mask('#.##0,00', {reverse: true});
	$('#btnSaveNewAcc').on('click', novaConta);
});