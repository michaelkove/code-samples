<?php

    namespace App\Models;

    use Illuminate\Database\Eloquent\Model;

    /**
     * Class Golf
     * @package App\Models
     */
    class Golf extends Model
    {

//    php artisan make:migration rename_golfs_total_per_roster_column
        protected $table = 'golfs';

        protected $fillable = [
            'golf_tournament_id',
            'pool_id',
            'number_of_groups',
            'number_of_winners',
            'number_of_round_winners',
            'total_per_entry',
            'toward_score',
            'playoff_stroke',
            'max_pool_per_user',
            'lower_to_higher',
	        'cut_position',
            'winner',
            'win_type',
            'config',
            'locked', //locks pool for editing by commissoner (Friday night entry remaval
            'pickable', //whether users can or cannot pick - controlled by commissioner
            'entry_lock', //locks entry for user picks - controlled by system either at tee off or by custom time.
            'type',
            'count_cut',
	        'cut_line',
            'locks_at',
	        'pdf_config'
        ];






        protected $casts = [
            'locked' => 'boolean',
            'pickable' => 'boolean',
            'entry_lock' => 'boolean',
        ];

        protected $with = [
//            'rosters',
//            'tournament',
//            'grouped_tournament_players',
        ];

        public function pool(){
            return $this->belongsTo(Pool::class,'pool_id','id');
        }

        public function tournament(){
            return $this->belongsTo(GolfTournament::class,'golf_tournament_id','id');
        }

        public function entries(){
            return $this->hasMany(GolfEntry::class,'golf_id', 'id');
        }

        public function groups(){
            return $this->hasManyThrough(GolfGroup::class, GolfEntry::class);
        }

        //backwards compat
        public function rosters(){
            return $this->entries();
        }

        public function grouped_tournament_players(){

            return $this->belongsToMany(
				GolfTournamentPlayer::class,
				'golf_tournament_players_grouping',
				'golf_id',
				'gtp_id')
                        ->withPivot(
							'group_number',
							'stats',
							'handicap',
							'percent',
	                        'round_1',
	                        'round_2',
	                        'round_3',
	                        'round_4',
	                        'total',
                        );
        }
		
		public function handicapped_grouped_tournament_players(){
			return $this->grouped_tournament_players()->wherePivot('handicap','<>', null);
        }




        public function getConfigAttribute($value)
        {
            return json_decode($value, false);
        }

        public function setConfigAttribute($data){
            return $this->attributes['config'] = json_encode($data);
        }
	
	
	    public function getPdfConfigAttribute($value)
	    {
		    return json_decode($value, false);
	    }
	
	    public function setPdfConfigAttribute($data){
		    return $this->attributes['pdf_config'] = json_encode($data);
	    }


        public function getConfig($key = null){
            if($key){
                if(isset($this->config) && $this->config->{$key}){
                    return $this->config->{$key};
                }
            }
            return null;
        }

    }
