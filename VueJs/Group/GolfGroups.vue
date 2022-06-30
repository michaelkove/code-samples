<template>
    <div>
        <div class="golfGroupsContainer">
            <golf-group
                :group.sync="group"
                :pending.sync="pending"
                :golf.sync="golf"
                :players.sync="players"
                :groupcount="getGroups.length"
                :key="group.number"
                v-for="group in getGroups"
                @move-player="movePlayer"
                @remove-player="removePlayer"
            >

            </golf-group>
        </div>
    </div>
</template>

<script>
    import GolfGroup from "./GolfGroup";
    export default {
        name : "GolfGroups",
        components: {GolfGroup},
        props: [
            "golf","players", "pending"
        ],
        data: function () {
            return {

            }
        },
        computed : {
            getGroups : function(){
                if(this.golf){
                    return this.golf.groups;
                }
                return [];
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
