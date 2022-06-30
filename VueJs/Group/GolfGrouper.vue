<template>
    <div>
        <golf-group-form
            :pending.sync="pending"
            :max_players.sync="max_players"
            @save-grouping="saveGrouping"
            @auto-populate="autoPopulate"
            v-if="!processing"
        ></golf-group-form>
        <div v-else>
            <i class="fa fa-spin fa-golf-ball"></i> Processing...
        </div>
        <golf-groups
        :golf.sync="golf"
        :pending.sync="pending"
        @move-player="movePlayer"
        @remove-player="removePlayer"
        ></golf-groups>

        <golf-players
            v-if="!processing"
            :golf.sync="golf"
            :pending.sync="pending"
            :players.sync="players"
            @move-player="movePlayer"
        ></golf-players>

    </div>
</template>

<script>
    import GolfGroups from "./GolfGroups";
    import GolfPlayers from "./GolfPlayers";
    import GolfGroupForm from "./GolfGroupForm";
    export default {
        name : "GolfGrouper",
        components: {GolfGroupForm, GolfPlayers, GolfGroups},
        props: [
            "golf","pending","players", "max_players", "processing", "dist"
        ],
        data: function () {
            return {
				missing : [],
            }
        },
	    created() {
			this.collectMissingIds();

	    },
	    computed : {

            prettyTest : function(){
                if(this.golf){
                    return  this.golf.name + "  "+ this.golf.id;
                }
                return " ... ";
            }

        },
        methods:{
			collectMissingIds : function(){
				if(this.golf){
					this.golf.groups.forEach((group) =>{
						group.players.forEach((player)=>{
							if(!player.player){
								this.missing.push(player.id);
							}
						})
					})
				}

			},
            saveGrouping : function(){
                this.$emit('save-grouping');
            },
            autoPopulate : function(number){
                this.$emit('auto-populate',number);
            },
            movePlayer : function(playerId, groupNumber, originalGroupNumber = null){
                this.$emit('move-player', playerId, groupNumber, originalGroupNumber);
            },
            removePlayer : function(playerId, originalGroupNumber){
                this.$emit('remove-player', playerId, originalGroupNumber);
            }
        }
    }
</script>
