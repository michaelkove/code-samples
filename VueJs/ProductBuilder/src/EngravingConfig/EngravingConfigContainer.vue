<template>
	<div class="vpb--ConfigContainer">
		<div class="vpb--ConfigHead vpb--pointer" @click="openSection()">
			<h4 class="vpb--ConfigHeader">Engraving</h4>
			<div class="vpb--ConfigPick" v-html="getEngraving"></div>
		</div>
		<div class="vpb--ConfigBody" v-if="showSection">
			<div class="vbp--ConfigEngravingContainer">
				<p>Select type style:</p>
				<div class="vbp--ConfigEngravingType">
					<engraving-type
						:type="'none'"
						:selected-type.sync="getEngravingType"
						:type-string="'None'"
						@set-type="setType"
					></engraving-type>
					<engraving-type
						:type="'block'"
						:selected-type.sync="getEngravingType"
						:type-string="'BLOCK'"
						@set-type="setType"
					></engraving-type>
					<engraving-type
						:type="'italic'"
						:selected-type.sync="getEngravingType"
						:type-string="'Italic'"
						@set-type="setType"
					></engraving-type>
					<engraving-type
						:type="'handwritten'"
						:selected-type.sync="getEngravingType"
						:type-string="'Handwritten'"
						@set-type="setType"
					></engraving-type>

				</div>
				<div class="vbp--ConfigureEngravingMessageArea">
					<textarea rows="3" class="vbp--ConfigureEngravingMessageInput"
							  ref="engravemess"
							  :value="message"
							  placeholder="Type your message here"
							  @keyup="setEngravingMessage"
					></textarea>
					<span class="vbp--ConfigureEngravingCharcount" v-html="getCharCount"></span>
				</div>
			</div>
		</div>
	</div>
</template>


<script>


import EngravingType from "./EngravingType.vue";
export default {
	components: {
		EngravingType

	},
	props : [
		'message',
		'type',
		'open'
	],
	name : "EngravingConfigContainer",
	data() {
		return {
			localMessage : (this.message) ? this.message : "",
			localType : (this.type) ? this.type  : "none"
		};
	},
	mounted() {
		this.localType = (this.type) ? this.type  : "none";
		this.localMessage = (this.message) ? this.message : "";
		console.log("Mount ", this.type, this.message, this.localType, this.localMessage);
	},
	created() {
		this.localType = (this.type) ? this.type  : "none";
		this.localMessage = (this.message) ? this.message : "";
		console.log("Creating ", this.type, this.message, this.localType, this.localMessage);
	},
	computed : {
		getThisClass : function(){
			return 'vbp--ConfigEngravingTypeItem vbp--ConfigEngravingTypeItem--None ';
		},
		getCharCount : function(){
			let curCount = (this.localMessage) ? parseInt(this.localMessage.length) : 0;
			let remaining = (40 - curCount);
			return "Characters remaining  <strong>"+remaining+"</strong>";
		},
		getEngravingType : function(){
			return this.type;
		},
		isExpanded : function(){
			return this.expanded;
		},
		getEngraving : function(){
			if(this.message){
				let tp = (this.type != 'none' && this.type) ? " ["+this.type+"]" : "";
				return (this.message) ? this.message+tp : "-NONE-";
			}
			return "-create-";
		},
		getPickName : function(){
			return (this.localMessage) ? this.localMessage : "Select";
		},
		showSection : function(){
			return this.open;
		}
	},
	methods : {
		setType  : function(type){
			this.localType = type;
			this.localMessage = this.$refs.engravemess.value;
			this._updateEngraving();
		},
		setEngravingMessage : function(){
			this.localMessage = this.$refs.engravemess.value;
			this._updateEngraving();
		},
		openSection : function(){
			this.$emit('open-section','engraving');
		},
		_updateEngraving : function(){
			this.$emit('set-engraving', this.localType, this.localMessage);
		}
	}
}
</script>