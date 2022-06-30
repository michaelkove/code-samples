<template>
	<div class="vpb--ConfigContainer">
		<div class="vpb--ConfigHead vpb--pointer" @click="openSection()">
			<h4 class="vpb--ConfigHeader">Band Width</h4>
			<div class="vpb--ConfigPick" v-html="getWidthLabel"></div>
		</div>
		<div class="vpb--ConfigBody" v-if="showSection">
			<div class="vpb--widthChoicesContainer">
				<width-choice
					v-for="w in getWidthChoices" :key="w.id"
					:width="w"
					:choice.sync="getWidthChoice"
					@pick-width="pickWidth"
				>
				</width-choice>
			</div>
		</div>
	</div>
</template>


<script>


import WidthChoice from "./WidthChoice.vue";
export default {
	components: {
		WidthChoice

	},
	props : [
		'widths',
		'width',
		'open'
	],
	name : "WidthConfigContainer",
	data() {
		return {
			localWidth : null,
			expanded : false,
		};
	},
	created() {
		this.localWidth = this.width;
	},
	computed : {
		getWidthLabel : function(){
			if(this.width){
				if(this.width.name == '0.00') return "standard";
				return this.width.name+"mm";
			}
			return "select";
		},
		getWidthChoices : function(){
			return this.widths;
		},
		isExpanded : function(){
			return this.expanded && this.widths && this.widths.length;
		},

		getWidthChoice : function(){
			return this.width;
		},
		showSection : function(){
			return this.open && this.widths && this.widths.length;
		}
	},
	methods : {
		pickWidth : function(width){
			this.$emit('pick-width', width)
		},
		openSection : function(){
			this.$emit('open-section','width');
		}

	}
}
</script>