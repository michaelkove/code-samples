<template>
  <div class="col-sm-12 col-md-6 col-lg-4">
    <div class="btn-group m-2" style="width: 100%">
      <div
        class="btn btn-xs brand-btn border-brand-darkgrey golf-player-photo"
        :style="getPlayerPhotoStyle"
      ></div>
      <span
        class="btn btn-xs brand-btn border-brand-darkgrey text-left"
        :style="getStyle"
      >
        <span class="text-brand-orange">{{
		        getPlayersRank
	        }}</span> {{ getPlayerName }} <span v-if="(player.amateur)"><br><small>AMATEUR</small></span>
        <div
          class="bg-brand-red text-white"
          :style="getTagStyle"
          v-if="getSoftStatus"
        >
          {{ getSoftStatus }}
        </div>
      </span>
      <button
        class="
          btn btn-xs btn-brand-blue
          text-white
          brand-btn
          border-brand-darkgrey
          btn-flat
        "
        style="width: 25px; max-width: 20px; min-width: 25px"
        v-for="group in
         golf.groups"
        @click="movePlayer(player.id, group.number)"
      >
        {{ group.name }}
      </button>
    </div>
  </div>
</template>

<script>
export default {
  name: "GolfPlayer",
  props: ["player", "pending", "golf"],
  data: function () {
    return {
      width: window.innerWidth,
    };
  },
  mounted() {
    window.addEventListener("resize", () => {
      this.width = window.innerWidth;
    });
  },
  computed: {
    getStyle: function () {
      let style = "overflow-x: hidden;font-size: 11px; line-height: 0.95em;";
      let amateur = this.player.amateur ? " color: gray !important;" : "";
      return style + amateur;
    },
    getTagStyle: function () {
      return (
        "display: block;\n" +
        "    position: absolute;\n" +
        "    top: 0;\n" +
        "    right: 0;\n" +
        "    padding-left: 18px;\n" +
        "    padding-right: 7px;\n" +
        "    border-bottom-left-radius: 30px;\n" +
        "    font-size: 0.8em;"
      );
    },
    getSoftStatus: function () {
      if (this.player.config) {
        return this.player.config.soft_status &&
          this.player.config.soft_status !== "Active"
          ? this.player.config.soft_status
          : null;
      }
      return false;
    },
    getPlayerName: function () {
		if(!this.player.player) return "INACTIVE";
	    return this.width > window.responsiveSizes.tablet
          ? this.player.player.name
          : this.player.player.display_name_mobile;
      return pname;
    },
    getPlayersRank: function () {
      if (this.player && this.player.player) {
        // return this.player.rank;
        // console.log(this.player.pos);
        return this.player.player.pos !== 9999 &&
          this.player.player.pos !== 99999 &&
          this.player.player.pos
          ? this.player.player.pos
          : "N/R";
      }
      return "";
    },
    getPlayerPhotoStyle: function () {
	    if(!this.player.player) return "";
      let imgRender =
        "image-rendering: -webkit-optimize-contrast;\n" +
        "    background-position: 50% 50%;\n" +
        "    image-rendering: crisp-edges;\n" +
        "    image-rendering: -moz-crisp-edges;\n" +
        "    image-rendering: -o-crisp-edges;\n" +
        "    image-rendering: -webkit-optimize-contrast;\n" +
        "    -ms-interpolation-mode: nearest-neighbor;";
      let photo =
        (this.player.player.photo != null &&
        this.player.player.photo != "no-photo.png")
          ? this.player.player.photo
          : this.player.player.country_code + ".svg";
      return (
        "background-image: url('/assets/images/golf/" +
        photo +
        "');" +
        imgRender
      );
    },
  },
  methods: {
    movePlayer: function (playerId, groupNumber, originalGroupNumber = null) {
      this.$emit("move-player", playerId, groupNumber, originalGroupNumber);
    },
  },
};
</script>
