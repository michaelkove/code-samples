<template>

	<div :class="'vpb--metalChoiceItem vpb--pointer'+activeClass" @click="pickMetal()">
		<div :class="'vpb--metalColor vpb--metalColor-'+metal.color"></div>
		<div class="vpb--metalChoiceDescription">
			<div class="vpb--metalChoiceName" v-html="getMetalLabel"></div>
			<div class="vpb--widthChoiceAdditionalPrice">+£0</div>
		</div>
	</div>

</template>


<script>


export default {
	components: {

	},
	props : [
		'choice',
		'metal',
	],
	name : "MetalChoice",
	data() {
		return {
		};
	},
	created() {

	},
	computed : {
		getMetalColor : function(){
			return (this.metal) ? this.metal.color : "";
		},
		getMetalLabel : function(){
			let karat = (this.metal.karat) ? this.metal.karat+"cc " : "";
			return karat+this.metal.color;
		},
		activeClass : function(){
			return (this.choice && this.metal.id == this.choice.id) ? " wpb--metalChoiceItem-active " : "";
		},

	},
	methods : {
		pickMetal : function(m){
			this.$emit('pick-metal', this.metal);
		}

	}
}
</script>