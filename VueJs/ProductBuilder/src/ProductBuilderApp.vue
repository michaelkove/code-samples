<template>
	<div id="vpbProductBuilderApp">
		<h1>Configure Your Ring</h1>
		<hr>
		<div v-if="productFullyLoaded">
			<a href="#"><< BACK</a>
			<div class="vpb--productContainer">
				<div class="vpb--productStage">
					<div class="vpb--productImages">
						<img v-for="img in getProductImages" :src="img" :alt="currentProduct.name">
					</div>
					<div v-if="productDetails" class="vpb--productDetails">
						<h3 class="vpb--productDetailsLabel">
							PRODUCT DESCRIPTION
						</h3>
						<div class="vpb--productDetailsArea">
							<p v-html="currentProduct.description"></p>
						</div>
						<h3 class="vpb--productDetailsLabel">
							PRODUCT DETAILS
						</h3>
						<div class="vpb--productDetailsArea">
							<div class="vpb--col-50">
								<p><strong>Metal:</strong> <span v-html="(userConfig.metal) ? userConfig.metal.name : '-' "></span></p>
								<p><strong>Stone:</strong> <span>-</span></p>
								<p><strong>Stone Colour</strong> <span>-</span></p>
							</div>
							<div class="vpb--col-50">
								<p><strong>Warrant:</strong> <span>12 months</span></p>
								<p><strong>Brand:</strong> <span>Brown & Newrich</span></p>
								<p><strong>Gender:</strong> <span>-</span></p>
							</div>
						</div>

					</div>
				</div>
				<div class="vpb--productConfig">
					<div v-if="currentProduct">
						<h3 class="vpb--brandName" v-html="getBrandName"></h3>
						<h4 class="vpb--ringName" v-html="getRingName"></h4>
						<div class="vpb--fromPriceContainer">
							from <span class="vpb--estimatedPrice" v-html="_getFromPrice(currentProduct.rrp_ladies)"></span>
						</div>
						<div class="vpb--inStock">
							<span>To Order</span>
						</div>
						<div id="vpbConfigContainer" class="vbp--productConfigContainer">
							<metal-config-container
								:open.sync="metalOpen"
								:metals.sync="getAvailableProductMetals"
								:metal.sync="getConfigedMetal"
								@pick-metal="pickMetal"
								@open-section="openSection"
							></metal-config-container>
							<width-config-container
								:open.sync="widthOpen"
								:widths.sync="getAvailableProductWidths"
								:width.sync="getConfigedWidth"
								@pick-width="pickWidth"
								@open-section="openSection"
							>
							</width-config-container>
							<size-config-container
								:open.sync="sizeOpen"
								:sizes.sync="getAvailableProductSizes"
								:size.sync="userConfig.size"
								@pick-size="pickSize"
								@open-section="openSection"
							>
							</size-config-container>
							<engraving-config-container
								:open.sync="engravingOpen"
								:message.sync="getUserConfigMessage"
								:type.sync="getUserConfigMessageType"
								@open-section="openSection"
								@set-engraving="setEngraving"
							>
							</engraving-config-container>
							<price-container
								:completed.sync="isReadyToBuy"
								:price.sync="userConfig.price"
								@buy-product="buyProduct"></price-container>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div v-else>
			<div class="vpb--LoadingContainer" v-if="showLoading">
				<div>
					<img src="/media/loading.svg" class="vpb--LoadingContainer--icon" alt="Loading...">
				</div>
			</div>
			<div v-else>
				<div v-if="!currentCategory">
					<h3>Categories</h3>
					<div class="vpb--categoryContainer">
						<div v-for="cat in getCategories" class="vpb--categoryItem vpb--pointer" @click="selectCategory(cat.name)">
							<img :src="cat.image" :alt="cat.name" class="vpb--categoryImg">
							<h4 class="vpb--ringName" v-html="cat.name"></h4>
							<div class="vpb--fromPriceContainer">
								From: <span class="vpb--estimatedPrice" v-html="_getFromPrice(cat.prices)"></span>
							</div>
						</div>
					</div>
				</div>
				<div v-else>
					<h3>Products By Category</h3>
					<div class="vpb--categoryContainer">
						<div v-for="product in productsByCategory" class="vpb--categoryItem vpb--pointer" @click="selectProduct(product.sku)">
							<img :src="_getFirstImage(product.images)" :alt="product.name" class="vpb--categoryImg">
							<h4 class="vpb--ringName" v-html="product.name"></h4>
							<div class="vpb--fromPriceContainer">
								From: <span class="vpb--estimatedPrice" v-html="_getFromPrice(product.rrp_ladies)"></span>
							</div>
							<p class="vpb--productDescription" v-html="product.description"></p>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</template>

<script>


import WidthConfigContainer from "./WidthConfig/WidthConfigContainer.vue";
import MetalConfigContainer from "./MetalConfig/MetalConfigContainer.vue";
import SizeConfigContainer from "./SizeConfig/SizeConfigContainer.vue";
import EngravingConfigContainer from "./EngravingConfig/EngravingConfigContainer.vue";
import PriceContainer from "./PriceBox/PriceContainer.vue";

import axios from 'axios';

export default {
	components: {
		PriceContainer,
		EngravingConfigContainer,
		SizeConfigContainer,
		MetalConfigContainer,
		WidthConfigContainer,
	},
	name : "ProductBuilderApp",
	data() {
		return {
			query : {
				sku : null,
				metal : null,
				size : null,
				message : null,
				type : null,
			},
			purchased : false,
			sku : null,
			metalOpen : true,
			widthOpen : false,
			sizeOpen : false,
			engravingOpen : false,
			userConfig : {
				sku : null,
				name : null,
				metal : null,
				width : null,
				size : null,
				engrave : {
					type: null,
					message: null,
				},
				price : null,
			},
			currentCategory : null,
			currentProduct : null,
			productDetails : null,
			products : null,
			errorMessage : null,
			categories : [],
			endpoints : {
				url : URL,
				products: 'getproducts',
				details : 'getproductdetails',
				addToCart : 'addtocart',
			},
		};
	},
	created() {
		this.query.sku = this.$route.query.sku;
		this.query.metal = this.$route.query.metal;
		this.query.width = this.$route.query.width;
		this.query.size = this.$route.query.size;
		this.query.message = this.$route.query.message;
		this.query.type = this.$route.query.type;
		let sdds = this.getProducts();

	},
	computed : {
		showLoading : function(){
			return (this.query.sku);
		},
		getBrandName : function(){
			return "Allum & Sidaway";
		},
		getRingName : function(){
			return (this.currentProduct) ? this.currentProduct.name : "";
		},
		getEstimatedPrice : function(){
			if(this.userConfig.price){
				return this.currencyFormat(this.userConfig.price);
			}

			return this.currencyFormat(this._getFromPrice(this.currentProduct.rrp_ladies));
			// return this.currentProduct.
		},
		getLocalProducts : function(){
			return this.products;
		},
		getCategories : function(){
			return this.categories;
		},
		productsByCategory : function(){
			let currentCategoryName = this.currentCategory.name;
			return this.products.filter(function(p){
				return p.name === currentCategoryName;
			})
		},
		productFullyLoaded : function(){
			return (this.productDetails) ? true : false;
		},
		getCurrentProductConfig : function(){
			return this.currentProduct;
		},
		getConfigedMetal : function(){
			return this.userConfig.metal;
		},
		getConfigedWidth : function(){
			return this.userConfig.width;
		},
		getAvailableProductMetals : function(){
			return this.currentProduct.config.metals;
		},
		getAvailableProductWidths : function(){
			let cmId = (this.userConfig.metal) ? this.userConfig.metal.id : null;
			let metal = (cmId) ? this._getMetalById(cmId) : null;
			return (metal) ? metal.widths : [];
		},
		getAvailableProductSizes : function(){
			let cwId = (this.userConfig.width) ? this.userConfig.width.id : null;
			let cmId = (this.userConfig.metal) ? this.userConfig.metal.id : null;
			let width = (cwId) ? this._getWidthById(cwId, cmId) : null;
			return (width) ? width.sizes : [];
		},
		getProductImages : function(){
			return this.currentProduct.images.split(',');
		},
		isReadyToBuy : function(){
			return (this.userConfig.price && this.userConfig.width && this.userConfig.metal && this.userConfig.size && this.userConfig.sku);
		},
		getUserConfigMessage : function(){
			return this.userConfig.engrave.message;
		},
		getUserConfigMessageType : function(){
			return this.userConfig.engrave.type;
		},
		opTba : function(){
			let price = parseInt(this.userConfig.price);
			let obs = ((price * 12532) +123);
			let hash = "aI"+obs.toString().replace('1','bANs').replace('5', 'zEf')+"-111AA22Z";
			return hash;

		}
	},
	methods : {
		setCurrent : function(sku){
			this.currentProduct = this.products.find(function(p){
				return p.sku === sku;
			});
		},
		pickProduct : function(){
			this.setCurrent(this.sku);
			this.getProductDetails(this.sku);
			// this.buildProductConfig();
		},
		getProducts : async function(){
			let url = this.endpoints.url+this.endpoints.products;
			return fetch(url)
				.then(response => response.json())
				.then(data => {
					this.products = data.response.products;
					this.buildCategories();
				})
				.catch(error => {
					this.errorMessage = error;
				}).then((d) => {
					if(this.query.sku){
						this.selectProduct(this.query.sku.toUpperCase());
					}
				});
		},
		getProductDetails : function(sku){
			let url = this.endpoints.url+this.endpoints.details+"?sku="+sku;
			return fetch(url)
				.then(response => response.json())
				.then(data => {
					this.productDetails = data.response.products;
					this.buildProductConfig();
					this.userConfig.sku = this.currentProduct.sku;
					this.userConfig.name = this.currentProduct.name;
				})
				.catch(error => {
					this.errorMessage = error;
				}).then((d)=>{
					this._loadProductConfigFromQueryStrings();
				});
		},
		_loadProductConfigFromQueryStrings : function(){
			if(this.query.metal){
				let mId = this.query.metal;
				let m = this._getMetalById(mId);
				this.pickMetal(m);
				if(this.query.width){
					let wId = this.query.width;
					let w = this._getWidthById(wId, mId);
					this.pickWidth(w);
					if(this.query.size){
						let sId = this.query.size.toString();
						let s = this._getSizeById(sId.toLowerCase(), wId, mId);
						this.pickSize(s.id);
					}
				}
				if(this.query.message || this.query.type){
					let m = this.query.message;
					let t = this.query.type;
					this.setEngraving(t, m);
				}
			}
		},
		buildCategories : function(){
			let curname = null;
			this.products.forEach((p) =>{
				if(curname !== p.name && !this._skipCat(p.name)){

					let category = {
						name : p.name,
						image : this._getFirstImage(p.images),
						prices : p.rrp_ladies
					}
					this.categories.push(category);
					curname = p.name;
				}
			})
		},
		_skipCat : function(name){
			const regex = /Earrings|Pendant/g;

			return (name.search('Earrings') != -1 || name.search('Earring') != -1 || name.search('Pendant') != -1);

			return false;
			return name.matchAll(/Earrings|Pendant/g);
		},
		selectCategory : function(name){
			this.currentCategory = this.categories.find(function(c){
				return c.name === name;
			});
		},
		selectProduct : function(sku){
			this.sku = sku;
			this.pickProduct();
			this.query.sku = this.sku;
			this.$router.push({query: this.query}).catch(()=>{});
		},
		getPriceByMetalWidthSize : function(metalId, widthId, sizeId){
			// console.log("Doing sizing ", metalId, widthId, sizeId);
			let filtered = this.productDetails.filter((pd) => {
				return (pd.metal == metalId && pd.width == widthId);
			});
			// console.log("Found filtered ", filtered);

			let foundDetail = this.productDetails.find((pd) => {
				return (pd.metal == metalId && pd.width == widthId && this._isNumSizeInRange(sizeId.toLowerCase(), pd.size.toLowerCase()));
			})
			if(!foundDetail){

			}
			return (foundDetail) ? foundDetail.rrp : null;
		},
		buildProductConfig : function(){
			this.currentProduct.config = {
				metals : this.buildMetals()
			};
		},
		buildMetals : function(){
			let metals = [];
			let availableMetals = this._getAvailableMetals(this.currentProduct.metals);
			let only = [
				'18R', '18W', '18Y', '9R', '9W', '9Y', "PLT"
			];
			availableMetals.forEach((m)=>{
				if(only.includes(m.id)){
					let metal = {
						id : m.id,
						color : m.color,
						name : m.name,
						karat : m.karat,
						widths: this.buildWidths(m.id)
					};
					metals.push(metal);
				}
			});
			return metals;
		},
		buildWidths : function(metalId){
			let widths = [];
			let availableWidths = this._getAvailableWidths(this.currentProduct.widths);
			availableWidths.forEach((w) => {
				let width = {
					id : w.id,
					name : w.name,
					sizes : this.buildSizes(metalId,  w.id)
				}
				widths.push(width);
			});
			return widths;
		},
		buildSizes : function(metalId, widthId){
			let sizes = [];
			let availableSizes = this._getAvailableSizes(this.currentProduct.sizes);
			availableSizes.forEach((s) => {

				let price = this.getPriceByMetalWidthSize(metalId, widthId, s.priced);
				if(price){
					let size = {
						id : s.id,
						name : s.name,
						price : price,
					}
					sizes.push(size);
				}

			})
			return sizes;

		},
		_explodeImages : function(images){
			if(images){
				return images.split(',');
			}
			return [];
		},
		_getFirstImage : function(images){
			let imagesArr = this._explodeImages(images);
			if(imagesArr[0].length){
				return imagesArr[0];
			}
			return "";
		},
		_getPriceRange : function(prices){

			if(prices){
				let clean = prices.replace(/\s+/g, '').trim();
				let priceArr = clean.split('-');
				return {
					start : priceArr[0],
					end : (priceArr.length >1) ? priceArr[1] : priceArr[0]
				}
			}
			return {
				start : 0,
				end : 0
			}
		},
		_getFromPrice : function(prices){
			let range = this._getPriceRange(prices);
			return this.currencyFormat(range.start);
		},
		currencyFormat : function(num) {
			num = parseFloat(num);
			return '£' + num.toFixed(2).replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,')
		},
		_getAvailableMetals : function(metalString){
			let metals = [];
			metalString = metalString.trim().toUpperCase();
			let metalArray = metalString.split(',');
			metalArray.forEach((m) => {
				let metal = {
					id : m,
					name : this._getPrettyMetalName(m),
					color : this._getMetalColorById(m),
					karat : this._getMetalKaratById(m),
				}
				metals.push(metal);
			});
			return metals;
		},
		_getMetalKaratById : function(raw){
			let matches = raw.match(/(\d+)/g);
			if(matches && matches.length && matches[0]){
				return matches[0];
			}
			return null;
		},
		_getMetalColorById : function(raw){
			let whites = /WF|W/g;
			let palladium = /PD500|PD/g;
			let platinum = /PLT/g;
			let roses = /RF|R/g;
			let golds = /YF|Y|K/g;
			if(raw.match(whites)){
				return "White";
			}
			if(raw.match(roses)){
				return "Rose";
			}
			if(raw.match(golds)){
				return "Gold";
			}
			if(raw.match(palladium)){
				return "Palladium";
			}
			if(raw.match(platinum)){
				return "Platinum";
			}
			return null;
		},
		_getPrettyMetalName : function(raw){
			// replace specials
			let lrw = raw;
			lrw.toUpperCase();
			lrw.replace(/PLT/g, 'Platinum');
			lrw.replace(/PD500/g, 'Palladium 500');
			lrw.replace(/PD/g, 'Palladium');
			// karats
			lrw.replace(/WF/g, 'K White Finish Gold');
			lrw.replace(/YF/g, 'K Yellow Finish Gold');
			lrw.replace(/RF/g, 'K Rose Finish Gold');
			lrw.replace(/W/g, 'K White Gold');
			lrw.replace(/Y/g, 'K Gold');
			lrw.replace(/R/g, 'K Rose Gold');
			lrw.replace(/K/g, 'K Gold');
			return lrw;
			// 14W,14Y,18R,18W,18WF,18Y,18YF,22K,9K,9R,9W,9Y,PD,PD500,PLT
		},

		_getAvailableWidths : function(widthString){
			let widths = [];
			widthString = widthString.trim().toUpperCase();
			let widthArray = widthString.split(',');
			widthArray.forEach((w) => {
				let width = {
					id : w,
					name : w,
				}
				widths.push(width);
			});
			return widths;
		},
		_getAvailableSizes : function(sizesRaw){
			return this._createSizeChart();

		},
		_getSizeRange : function(range){
			if(range.toLowerCase() == 'z+'){
				let s = "Z";
				// return {
				//
				// }
			}

			if(range){
				let clean = range.replace(/\s+|\+/g, '').toLowerCase().trim();
				let rangeArr = clean.split('-');
				// funny end math
				let end = (rangeArr.length > 1) ? rangeArr[1] : rangeArr[0];

				return {
					start : rangeArr[0],
					end : end
				}
			}
			return {
				start : null,
				end : null
			}
		},
		_createSizeChart : function(){
			let sizeChart = [];

			let litm = 0;
			for (let i = 0; i < 26; i++) {
				litm = i;
				let l = (i+10).toString(36);
				let size = {
					id : l.toLowerCase(),
					priced : l.toLowerCase(),
					num : i,
					name : l
				};
				sizeChart.push(size);

				let	sizeHalf = {
						id : l.toLowerCase()+"+",
						priced : l.toLowerCase(),
						num : i+0.5, //possibly i + .5 for upper level if Q.5 falls outside of Q range..
						name : l.toUpperCase()+".5"
					};

				sizeChart.push(sizeHalf);
			}
			let	zSize = {
				id : "z+",
				priced : "z+",
				num : litm+1, //possibly i + .5 for upper level if Q.5 falls outside of Q range..
				name : "Z+"
			};
			sizeChart.push(zSize)
			return sizeChart;
		},
		_isNumSizeInRange : function(size, range){


			let start = 0;
			let end  = 0;
			if(range == 'z+' && size){
				start = this._letterToNum('z');
				end = this._letterToNum('z');
			} else {
				let cleanRange = this._getSizeRange(range);
				start = (cleanRange.start) ? this._letterToNum(cleanRange.start) : 0;
				end = (cleanRange.end) ? this._letterToNum(cleanRange.end) : 0;
			}
			let cur = this._letterToNum(size);
			return (start <= cur && cur <= end);
		},
		_letterToNum : function(letter){
			return letter.charCodeAt(0) - (letter === letter.toLowerCase() ? 96 : 64);
		},
		pickMetal : function(metal){
			if(metal){
				this.$set(this.userConfig,"width", null);
				let pickedMetal = {
					id : metal.id,
					color : metal.color,
					karat : metal.karat,
					name : metal.name,
				};
				this.$set(this.userConfig, "metal", pickedMetal);
				this.metalOpen = false;
				this.widthOpen = true;
				this.calculatePrice();
				this.query.metal = pickedMetal.id;
				this.$router.push({query: this.query}).catch(()=>{});
			}

			//TODO: pdate prices
		},
		pickWidth : function(width){
			if(width){
				let pickedWidth = {
					id : width.id,
					name : width.name,
				}
				this.metalOpen = false;
				this.widthOpen = false;
				this.sizeOpen = true;
				this.$set(this.userConfig, "width", pickedWidth);
				this.query.width = pickedWidth.id;
				this.$router.push({query: this.query}).catch(()=>{});
				this.calculatePrice();
			}

		},
		pickSize : function(size){
			if(size){
				let pickedSize = {
					id : size.toLowerCase(),
					name : size,
				}
				this.metalOpen = false;
				this.widthOpen = false;
				this.sizeOpen = false;
				this.engravingOpen = true;
				this.$set(this.userConfig, "size", pickedSize);
				this.query.size = pickedSize.id;
				this.$router.push({query: this.query}).catch(()=>{});
				this.calculatePrice();
			}

		},
		setEngraving : function(type, message){
			if(type || message){
				this.$set(this.userConfig.engrave,"type", type);
				this.$set(this.userConfig.engrave,"message", message);
				this.query.message = message;
				this.query.type = type;
				this.$router.push({query: this.query}).catch(()=>{});
				this.calculatePrice();
			}

		},
		openSection : function(section){
			if(section === 'metal'){
				this.metalOpen = true;
				this.widthOpen = false;
				this.sizeOpen = false;
				this.engravingOpen = false;
			} else if(section === 'width'){
				this.metalOpen = false;
				this.widthOpen = true;
				this.sizeOpen = false;
				this.engravingOpen = false;
			} else if(section === 'size'){
				this.metalOpen = false;
				this.widthOpen = false;
				this.sizeOpen = true;
				this.engravingOpen = false;
			} else if(section === 'engraving') {
				this.metalOpen = false;
				this.widthOpen = false;
				this.sizeOpen = false;
				this.engravingOpen = true;
			} else {
				this.metalOpen = false;
				this.widthOpen = false;
				this.sizeOpen = false;
				this.engravingOpen = false;
			}
		},
		resetAll : function(){
			this.sku = null;
			this.purchased = false;
			this.metalOpen = true;
			this.metalOpen = false;
			this.widthOpen = false;
			this.sizeOpen = false;
			this.engravingOpen = false;
			this.price = null;
			this.currentCategory = null;
			this.currentProduct = null;
			this.productDetails = null;
			// this.products = null;
			this.errorMessage = null;
			this.$set(this.userConfig, "sku", null);
			this.$set(this.userConfig, "name", null);
			this.$set(this.userConfig, "metal", null);
			this.$set(this.userConfig, "width", null);
			this.$set(this.userConfig, "size", null);
			this.$set(this.userConfig.engrave, "type", null);
			this.$set(this.userConfig.engrave, "message", null);
		},
		_getMetalById : function(mId){
			return this.currentProduct.config.metals.find(function(m){
				return m.id == mId;
			});
		},
		_getWidthById : function(wId, mId){
			let metal = this._getMetalById(mId);
			if(metal){
				return metal.widths.find(function(w){
					return w.id == wId;
				})
			}
			return null;
		},
		_getSizeById : function(sId, wId, mId){
				let width = this._getWidthById(wId, mId);
			if(width){
				return width.sizes.find(function(s){
					return s.id == sId;
				})
			}
			return null;
		},
		calculatePrice : function(){
			let mId = (this.userConfig.metal) ? this.userConfig.metal.id : null;
			let wId = (this.userConfig.width) ? this.userConfig.width.id : null;
			let sId = (this.userConfig.size) ? this.userConfig.size.id : null;
			let chosenSize = this._getSizeById(sId,wId, mId);
			let price = null;
			if(chosenSize){
				price = (chosenSize.price && chosenSize.price > 0) ? chosenSize.price : null;
			}
			this.$set(this.userConfig,"price", price);
		},
		buyProduct : function(){
			let postData = {
				sku: this.userConfig.sku,
				token : this.opTba,
				data: {
					metal: this.userConfig.metal.id,
					width: this.userConfig.width.id,
					size: this.userConfig.size.id.toUpperCase(),
					engraving: this.userConfig.engrave.message,
					engraving_type: this.userConfig.engrave.type
				}
			};
			let url = this.endpoints.url+this.endpoints.addToCart;
			const requestOptions = {
				method: "POST",
				headers: { "Content-Type": "application/json" },
				body: JSON.stringify({ title: "Vue POST Request Example" })
			};
			axios.post(url, postData)
				.then(data => {
					window.location = '/checkout/cart/';
				})
				.catch(error => {
					this.errorMessage = error;
				});
			this.purchased = true;
		}

	}
};
</script>

<style>


</style>