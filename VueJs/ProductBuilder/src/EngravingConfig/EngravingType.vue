<template>
	<div :class="getThisClass" v-html="typeString" @click="setType"></div>
</template>

<script>


export default {
	components: {

	},
	props : [
		'selectedType',
		'type',
		'typeString',
	],
	name : "EngravingType",
	data() {
		return {

		};
	},
	created() {

	},
	computed : {
		getThisClass : function(){
			let standartClass = 'vbp--ConfigEngravingTypeItem vbp--ConfigEngravingTypeItem--Container vpb--pointer vbp--ConfigEngravingTypeItem--'+this.type;
			let activeClass = (this.type === this.selectedType) ? ' vbp--ConfigEngravingTypeItem--active ' : '';
			return standartClass+activeClass;
		},
	},
	methods : {
		setType : function(){
			console.log("CLICK TYPE", this.type);
			this.$emit('set-type',this.type);
		},
	}
}
</script>