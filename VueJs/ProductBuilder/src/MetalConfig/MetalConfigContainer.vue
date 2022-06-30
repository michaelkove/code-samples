<template>
	<div class="vpb--ConfigContainer">
		<div class="vpb--ConfigHead">
			<h4 class="vpb--ConfigHeader">Metal</h4>
			<div class="vpb--ConfigPick vpb--pointer" @click="openSection()" v-html="getMetalLabel"></div>
		</div>
		<div class="vpb--ConfigBody" v-if="open">
			<div class="vpb--metalChoicesContainer">
				<metal-choice
					v-for="m in getMetalChoices" :key="m.id"
					:metal.sync="m"
					:choice.sync="getMetalChoice"
					@pick-metal="pickMetal"
				>
				</metal-choice>
			</div>
		</div>
	</div>
</template>


<script>


import MetalChoice from "./MetalChoice.vue";
export default {
	components: {
		MetalChoice

	},
	props : [
		'metals',
		'metal',
		'open'
	],
	name : "MetalConfigContainer",
	data() {
		return {
			localMetal : null,
			expanded : false,
			choice : null,
		};
	},
	created() {
	},
	computed : {
		getMetalLabel : function(){
			if(this.metal){
				let karat = (this.metal.karat) ? this.metal.karat+"cc " : "";
				return karat+this.metal.color;
			}
			return "select";
		},
		getMetalChoices : function(){
			return this.metals;
		},
		isExpanded : function(){
			return this.expanded;
		},
		getMetalChoice : function(){
			return this.metal;
		}
	},
	methods : {
		pickMetal : function(m){
			this.expanded = false;
			this.$emit('pick-metal', m);
		},
		openSection : function(){
			this.$emit('open-section','metal');
		}

	}
}
</script>