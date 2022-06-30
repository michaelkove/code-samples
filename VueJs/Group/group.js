import GolfGrouper from "./GolfGrouper";
window._ = require('lodash');

if(document.getElementById('golfGroupApp'))
{
    const golfGroupApp = new Vue({
        el: '#golfGroupApp',
        components: {
            GolfGrouper,
        },
        data: function () {
            return {
                golf : null,
                players : [],
                max_players : 0,
                pending : false,
                processing : false,
                dist : null
            }
        },
        created() {
            let id = document.getElementById('golfGroupApp').dataset.golf_id;
            this.getPoolGroups(id);
        },
        methods : {
            getPoolGroups : function(id){
                axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

                axios.get('/golf/'+id+'/get-vue-groups').then((jsonData) => {
                    this.golf = jsonData.data.golf;
                    this.players = jsonData.data.players;
                    this.max_players = jsonData.data.max_players;
                }).catch((err)=> {
                    // alert(err);
                });
            },
            autoPopulate : function(number){
                if(this.golf.pick_count > 0){
                    if (confirm('Users started picking players, autopicking will reset all picks')) {
                        this._saveGrouping("autopopulate", number);
                        return true;
                    } else {
                        return false;
                    }
                } else {
                    this._saveGrouping("autopopulate", number);
                }
            },
            saveGrouping : function(){
                if(this.golf.pick_count > 0){
                    if (confirm('Users started picking players, saving grouping will reset all picks')) {
                        this._saveGrouping("save");
                        return true;
                    } else {
                        return false;
                    }
                } else {
                    this._saveGrouping("save");
                }
            },
            _saveGrouping : function(op, number = null){
                this.processing = true;
                axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
                let _token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                let url = "/golf/"+this.golf.id+"/edit/grouping";
                number = number > this.max_players ? this.max_players : number;
                axios.post(url, {
                    op : op,
                    number : number,
                    groups : this.golf.groups,
                    _token : _token,
                })
                    .then((response) => {
                        if(response.data.error){

                        } else {

                            this.golf = response.data.data.golf;
                            this.players = response.data.data.players;


                            this.processing = false;
                            this.pending = false;
                            // this.dist = response.data.dist;
                        }
                    }, (error) => {
                        alert(error);
                    });
            },
            movePlayer : function(playerId, groupNumber, originalGroupNumber = null){
                //remove it if there is original group
                if(originalGroupNumber){
                    this.removePlayer(playerId, originalGroupNumber);
                }

                let parentGroup = this.golf.groups.find(function(group){
                    return (group.number === groupNumber)
                });


                let thisPlayer = this.players.find(function(player){
                    return (player.id === playerId);
                });

                if(thisPlayer){
                    let playerIndex = this.players.findIndex(function(player){
                        return (player.id === playerId);
                    });
                    //push
                    parentGroup.players.push(thisPlayer);
                    //remove from list
                    this.players.splice(playerIndex,1);
                } else {

                }
                // this.players.splice(playerIndex,1);
                this.pending = true;
            },
            removePlayer : function(playerId, originalGroupNumber){
                let parentGroup = this.golf.groups.find(function(group){
                    return (group.number === originalGroupNumber)
                });

                let player = parentGroup.players.find(function(player) {
                    return (player.id === playerId);
                });

                let playerIndex = parentGroup.players.findIndex(function(player){
                    return (player.id === playerId);
                });
                //Put in general list
                this.players.push(player);

                //Remove from original group
                parentGroup.players.splice(playerIndex,1);
                this.pending = true;
            }
        },
        computed : {

        }
    });
}
