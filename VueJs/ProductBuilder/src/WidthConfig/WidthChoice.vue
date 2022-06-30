<template>

	<div :class="'vpb--widthChoiceItem vpb--pointer'+activeClass" @click="pickWidth()">
		<div class="vpb--widthRect"  :style="getWidthStyle"></div>
		<div class="vpb--widthChoiceDescription">
			<div class="vpb--widthChoiceName" v-html="getWidthLabel"></div>
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
		'width',
	],
	name : "WidthChoice",
	data() {
		return {
		};
	},
	created() {

	},
	computed : {
		getWidthStyle : function(){
			return "width:"+15 * this.getWidthNumber +"px;"
		},
		getWidthNumber : function(){
			if(this.width.name == '0.00') return 1;
			return parseFloat(this.width.name);
		},
		getWidthLabel : function(){
			if(this.width.name == '0.00') return "STND";
			return this.width.name;
		},
		activeClass : function(){
			return (this.choice && this.width.id == this.choice.id) ? " wpb--widthChoiceItem-active " : "";
		},

	},
	methods : {
		pickWidth : function(m){
			this.$emit('pick-width', this.width);
		}

	}
}
</script>