<template>
	<div class="vpb--ConfigContainer vpb--text-center">
		<div class="vpb--ConfigHead">
			<h4 class="vpb--FinalPrice"  v-html="getPrice"></h4>
		</div>
		<div class="vpb--ConfigBody">
			<div class="vpb--metalChoicesContainer">
				<button :disabled="buyButtonDisabled" @click="buyProduct" class="vpb--AddToCartBtn">Add to Jewellery Bag</button>
			</div>
			<p class="vpb--disclaimer">
				Shipping and promotions calculated in checkout.<br>
				Click & Collect options also available.
			</p>
		</div>
	</div>
</template>


<script>


export default {
	components: {

	},
	props : [
		'price',
		'completed',
	],
	name : "PriceContainer",
	data() {
		return {

		};
	},
	created() {
	},
	computed : {
		buyButtonDisabled : function(){
			return !this.completed;
		},
		getPrice : function(){

			return (this.price) ? this.currencyFormat(this.price) : "";
		}
	},
	methods : {
		buyProduct : function(){
			this.$emit('buy-product');
		},
		currencyFormat : function(num) {
			num = parseFloat(num);
			return '£' + num.toFixed(2).replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,')
		},
	}
}
</script>