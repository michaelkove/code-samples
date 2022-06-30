<template>
	<div class="vpb--ConfigContainer">
		<div class="vpb--ConfigHead vpb--pointer" @click="openSection()">
			<h4 class="vpb--ConfigHeader">Ring Size</h4>
			<div class="vpb--ConfigPick" v-html="getSizeLabel"></div>
		</div>
		<div class="vpb--ConfigBody" v-if="showSection">
			<autocomplete
				:search="search"
				@submit="pickSize"
			></autocomplete>
			<span class="vpb--inputHelperText">Sizes start at '<strong v-html="startSize"></strong>'</span>
		</div>
	</div>
</template>


<script>

import Autocomplete from '@trevoreyre/autocomplete-vue'

export default {
	components: {
		Autocomplete,

	},
	props : [
		'sizes',
		'size',
		'open'
	],
	name : "SizeConfigContainer",
	data() {
		return {
			localSize : null,
			expanded : false,
		};
	},
	created() {
		this.localSize = this.size;
	},
	computed : {
		startSize : function(){
			return (this.sizes && this.sizes.length) ? this.sizes[0].name.toUpperCase() : "";
		},
		getSizeLabel : function(){
			return (this.size) ? this.size.name.toUpperCase() : "Select";
		},
		showSection : function(){
			return this.open && this.sizes && this.sizes.length;
		},
		pickableSizes : function(){
			let pSizes = [];
			this.sizes.forEach(function(s){
				pSizes.push(s.name.toUpperCase());
			});
			return pSizes;
		}
	},
	methods : {
		search : function(input) {
			if (input.length < 1) { return [] }
			return this.pickableSizes.filter(size => {
				return size.toUpperCase()
					.startsWith(input.toUpperCase())
			})
		},
		openSection : function(){
			this.$emit('open-section','size');
		},
		pickSize : function(result){
			this.$emit('pick-size',result);
		},
	}
}
</script>