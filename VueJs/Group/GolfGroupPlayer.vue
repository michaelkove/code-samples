<template>
    <li style="  break-inside: avoid-column;list-style: none; border: 1px solid gray;box-shadow:none; position: relative; margin-bottom: 2px; overflow: hidden; white-space: nowrap;margin:1px;">
        <div :class="getLiClass">
            <div class="btn-group m-0" style="width:100%;">

                <!--div class="btn btn-xs brand-btn golf-player-photo" :style="getPlayerPhotoStyle">

                </div-->
                <button class="btn btn-sm brand-btn btn-flat btn-brand-blue text-white border-brand-gray" :style="getRemoveButtonStyle" @click="removePlayer(player.id, group.number, group.number)">
                    <i class="fa fa-times"></i>
                </button>
                <div class="btn btn-sm btn-brand-black brand-btn btn-flat text-left" :style="getPlayerStyle">
                    <span class="text-brand-orange" v-html="getRank"></span> {{getPlayerName}} {{getAmateur}}
                    <div class="bg-brand-red text-white" :style="getTagStyle" v-if="getSoftStatus">{{getSoftStatus}}</div>
                </div>
                <button v-if="showLeft" class="text-center
                    btn btn-sm btn-brand-blue text-white brand-btn btn-flat" style="padding:2px; border:1px solid gray;box-shadow:none;width:25px;max-width:25px;min-width:25px;" @click="movePlayer(player.id, group.number - 1, group.number)">
                    <i class="fa fa-caret-left"></i>
                </button>
                <button v-if="showRight" class="text-center
                    btn btn-sm btn-brand-blue text-white brand-btn btn-flat" style="padding:2px; border:1px solid gray;box-shadow:none;width:25px;max-width:25px;min-width:25px;" @click="movePlayer(player.id, group.number + 1, group.number)">
                    <i class="fa fa-caret-right"></i>
                </button>


            </div>
        </div>





    </li>
</template>

<script>
    export default {
        name : "GolfGroupPlayer",
        props: [
            "player","pending", "group", "golf", "groupcount"
        ],
        data: function () {
            return {
                width : window.innerWidth,
	            ids : []
            }
        },
        mounted(){
            window.addEventListener("resize", ()=>{
                this.width = window.innerWidth
            });
        },
        computed : {
            getAmateur : function(){
                return (this.player.amateur) ? "(a)" : "";
            },
            getPlayerName : function(){
				if(this.player.player){
					return (this.groupcount > 4) ? this.player.player.display_name_mobile :   this.player.player.name
				}
               return "INACTIVE (ID:"+this.player.id+")";
            },
            getTagStyle : function(){
                return "display: block;\n" +
                    "    position: absolute;\n" +
                    "    top: 0;\n" +
                    "    right: 0;\n" +
                    "    padding-left: 18px;\n" +
                    "    padding-right: 7px;\n" +
                    "    border-bottom-left-radius: 30px;\n" +
                    "    font-size: 0.8em;"
            },
            getSoftStatus : function(){
                if(this.player.config){
                    return (this.player.config.soft_status && this.player.config.soft_status !== "Active") ? this.player.config.soft_status : null;
                }
                return false;
            },
            getLiClass : function(){

                let mob =  (this.width < 991) ? ' golfPlayerMobile ' : ' golfPlayerDesktop ';
                return "bg-brand-darkgrey border-brand-grey text-left text-white " + mob;
            },
            getPlayerStyle : function(){
              return (this.groupcount > 4) ? "border:1px solid gray;box-shadow:none;font-size: 9px; overflow-x: hidden; background:#000;" : "border:1px solid gray;box-shadow:none;font-size: 11px; overflow-x: hidden; background:#000;"
            },
            getRemoveButtonStyle : function(){
                return (this.groupcount > 4) ? "padding:2px; border:1px solid gray;box-shadow:none;width:20px;max-width:20px;min-width:20px" :  "padding:2px; border:1px solid gray;box-shadow:none;width:25px;max-width:25px;min-width:25px";
            },
            showLeft : function(){
                return (this.group.number > 1);
            },
            showRight : function(){
                return (this.group.number < this.golf.number_of_groups);
            },
            getPlayerPhotoStyle : function(){
                if(this.player.player){
                    return "background-image: url('/assets/images/golf/"+this.player.player.photo+"');border:1px solid gray;box-shadow:none;";
                }
				return ""
            },
            getRank : function(){
				if(this.player && this.player.player){
					return (this.player.player.pos !== 9999 && this.player.player.pos !== 99999 && this.player.player.pos !== 0) ? this.player.player.pos : "N/R";
				}
            }

        },
        methods:{
            movePlayer : function(playerId, groupNumber, originalGroupNumber = null){
                this.$emit('move-player', playerId, groupNumber, originalGroupNumber);
            },
            removePlayer : function(playerId, originalGroupNumber){
                this.$emit('remove-player', playerId, originalGroupNumber);
            }
        }
    }
</script>
