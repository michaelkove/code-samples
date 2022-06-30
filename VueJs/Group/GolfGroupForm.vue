<template>
    <div class="row mb-2">
        <div class="col-md-8 mx-auto">
            <div class="row">
                <div class="col-md-6 pt-1 mx-auto">
                    <form action="" method="post">
                        <input type="hidden" name="_token" :value="csrf">
                        <input type="hidden" name="op" value="autopopulate">
                        <label>Enter Number of Players to Use ( Field Size: {{max_players}} )</label>
                        <div class="row">
                            <div class="col-md-3">
                                <input type="number" min="0" :max="max_players" step="1" size="3" v-model="number" class="form-control" name="number" >
                            </div>
                            <div class="col">
                                <button class="btn btn brand-btn btn-flat btn-brand-blue" type="submit" ><i class="fa fa-sort-alpha-down-alt"></i> <span v-html="__c('golf.grouping.content.autopopuplate_button_label', 'Auto Populate & Save')"></span></button>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="col-md-4 pt-1">
                    <label for="">&nbsp;</label>
                    <div class="row">
                        <div class="col">
                            <button v-if="pending" @click="saveGrouping" class="btn btn-block btn-brand-green brand-btn"><i class="fa fa-save"></i> SAVE</button>
<!--                            <button v-else disabled="disabled" class="btn btn-block btn-brand-green btn-brand disabled"><i class="fa fa-save"></i> SAVE</button>-->
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </div>
</template>
<style>
    .input-group.special {
        display: flex;
    }

    .special .btn {
        flex: 1
    }
    .input-group>.input-group-prepend {
        flex: 0 0 30%;
    }
    .input-group .input-group-text {
        width: 100%;
    }
</style>
<script>
    export default {
        name : "GolfGroupForm",
        components: {},
        props: [
            "pending",
            "max_players"
        ],
        data: function () {
            return {
                number : null,
                csrf : window.Laravel.csrfToken
            }
        },
        computed : {
            getCSRF : function(){
                var csrf = document.querySelector("meta[name='csrf-token']").getAttribute('content');
                return
            },

        },
        methods:{
            saveGrouping : function(){
                this.$emit('save-grouping');
            },
            autoPopulate : function(number){
                this.$emit('auto-populate', number);
            }
        }
    }
</script>
