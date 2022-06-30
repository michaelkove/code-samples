<template>
    <div>
        <div class="row">
            <div class="col-md-12 text-center">
                <h4 v-html="__c('golf.grouping.content.players_label', 'Tournament Entrants')"></h4>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="row">
                    <golf-player
                        v-for="player in getPlayers"
                        :player.sync="player"
                        :golf.sync="golf"
                        :pending.sync="pending"
                        :key="player.id"
                        @move-player="movePlayer"
                    >
                    </golf-player>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    import GolfPlayer from "./GolfPlayer";
    export default {
        name : "GolfPlayers",
        components: {GolfPlayer},
        props: [
            "golf","pending","players"
        ],
        data: function () {
            return {

            }
        },
        computed : {
            getPlayers : function(){
                if(this.players){
                    return _.orderBy(this.players, 'rank');
                }
                return [];
            },


        },
        methods:{
            movePlayer : function(playerId, groupNumber, originalGroupNumber = null){
                this.$emit('move-player', playerId, groupNumber, originalGroupNumber);
            },
        }
    }
</script>
