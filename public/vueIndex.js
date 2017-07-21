$(document).ready(function(){
	var app = new Vue({
	    el : '#app',
	    data : {
	        mensagem: '!',
	        bankAccount1: {
	        	id: 0,
	        	name: 'Conta 1',
	        	balance: 0,
	        	operations: [],
	        },
	        withdrawalValueModel: 0,
	        depositValueModel:0,
	        withdrawalAccountModel: 0,
	        depositAccountModel:0,
	    },
	    methods:{
	    	loadDataFromAccount1: function () {
				var vm = this;
				axios.get('/v1/bankaccounts/search/1')
				.then(function (response){
					vm.bankAccount1 = response.data;
					console.log(response);
				})
				.catch(function(error){
					console.log(error);
				});
	    	},
	    	deposito: function (){
	    		var vm = this;
	    		axios.post('/v1/bankaccounts/deposit', {
					bank_account_id: vm.depositAccountModel,
					value: vm.depositValueModel,
	    		})
	    		.then(function(response){
	    			console.log(response);
	    			vm.loadDataFromAccount1();
	    		})
				.catch(function(error){
					console.log(error);
				});
	    	},
	    	saque: function (){
	    		var vm = this;
	    		axios.post('/v1/bankaccounts/withdrawal', {
					bank_account_id: vm.withdrawalAccountModel,
					value: vm.withdrawalValueModel,
	    		})
	    		.then(function(response){
	    			console.log(response);
	    			vm.loadDataFromAccount1();
	    		})
				.catch(function(error){
					console.log(error);
				});
	    	},
	    },
	    mounted: function (){
			var vm = this;
			// setInterval(function(){
			// 	vm.loadDataFromAccount1();
			// }, 10000);
			this.loadDataFromAccount1();
	    },
	});
});	