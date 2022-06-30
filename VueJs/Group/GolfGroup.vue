<template>
   <div class="golfGroupContainer p-1 " style="background: rgba(13,13,13,0.95);">
        <h5 class="text-center" v-if="groupcount > 1"> Group {{group.name}}</h5>
        <h5 class="text-center" v-else>Single Group</h5>
        <ul class="p-0" :style="getULStyle">

               <golf-group-player
                   v-for="player in getGroupPlayers"
                   :player.sync="player"
                   :golf.sync="golf"
                   :group.sync="group"
                   :key="player.id"
                   :groupcount="groupcount"
                   @move-player="movePlayer"
                   @remove-player="removePlayer"
               ></golf-group-player>
        </ul>

   </div>
</template>

<script>
    import GolfGroupPlayer from "./GolfGroupPlayer";
    export default {
        name : "GolfGroup",
        components: {GolfGroupPlayer},
        props: [
            "group","golf","pending","players", "groupcount"
        ],
        data: function () {
            return {
                width: window.innerWidth
            }
        },
        mounted(){
            window.addEventListener("resize", ()=>{
                this.width = window.innerWidth
            });
        },
        computed : {
            isMobile : function(){
                return (this.width < 850);
            },
            getULStyle : function(){
                if(this.groupcount === 1){
                    return  (this.isMobile) ? "column-count: 2;" : "column-count: 4;";
                }
                return "";
                // return "  height: 600px; display: flex; flex-direction: column;flex-wrap: wrap;"
            },
            getGroupPlayers : function(){
                if(this.group.players){
                    return _.orderBy(this.group.players, 'player.pos');
                }
                return [];
            }

        },
        methods :{
            movePlayer : function(playerId, groupNumber, originalGroupNumber = null){

                this.$emit('move-player', playerId, groupNumber, originalGroupNumber);
            },
            removePlayer : function(playerId, originalGroupNumber){
                this.$emit('remove-player', playerId, originalGroupNumber);
            }
        }
    }
</script>
