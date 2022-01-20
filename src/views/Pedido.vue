<template>
  <div>
    <b-row>
      <b-col cols="12">
        <b-btn
          class="ml-3 mt-3"
          @click.stop="listarMesas"
          variant="primary">Actualizar</b-btn>
        <b-card border-variant="primary" header-bg-variant="primary" header="<strong>Seleccionar Mesa</strong>" tag="article" class="m-3 mt-3">
          <b-row class="mt-1 mb-2">
            <b-col cols="12">
              <b-row v-if="items.length > 0">
                <b-col cols="6" sm="6" lg="2"  v-for="item in items" :key="'mesa_' + item.id" class="mesa">
                  <b-card border-variant="primary"
                    header-bg-variant="primary"
                    align="center" 
                    :no-body="true" 
                    footer-class="px-3 py-2"
                    @click="seleccionarMesa(item)">
                    <div slot="header" bg-variant="primary">
                      <div class="h5 mb-0 mt-2 text-center">{{item.descripcion}}</div>
                    </div>
                    <b-card-body class="p-3 clearfix text-center">
                      <!--<i :class="aplicacion.icono + ' bg-primary p-3 font-2xl mr-3 float-left'"></i>-->
                      <b-badge v-if="item.usada == null" variant="success">DISPONIBLE</b-badge>
                      <b-badge v-else variant="info">PEDIDO {{item.descripcionestadopedido}}</b-badge><br>
                      <b-badge v-if="item.usada" variant="secondary">MESERO: {{item.mesero}}</b-badge><br>
                      <b-badge v-if="item.usada" variant="dark">TIEMPO: {{item.minutos}} minutos</b-badge>
                    </b-card-body>
                    <div slot="footer">
                      <span>Ir a la mesa<i class="fa fa-angle-right float-right font-lg"></i></span>
                    </div>
                  </b-card>
                </b-col>
              </b-row><!--/.row-->
              <b-row v-else>
                <b-col cols="12" sm="6" lg="3">
                  No hay mesas registradas.
                </b-col>
              </b-row><!--/.row-->
            </b-col>
          </b-row>
          
        </b-card>
      </b-col>
    </b-row>
  </div>
</template>
<style scoped>
.columna-centrada {
  text-align: center;
}
.mesa {
  cursor: pointer;
}
.border {
  border: 7px solid #bd142b;
}
</style>

<script>
var self = this;
export default {
  name: "Pedido",
  data: function() {
    return {
      items: [],
      objeto: null,
      showModal: false,
      interval: null
    };
  },
  methods: {
    listarMesas: function() {
      this.$loader.open({ message: "Cargando ..." });
      var self = this;
      var frm = {};
      this.$http.get("ws/mesa/", frm).then(resp => {
          self.items = resp.data;
          if (self.items && self.items.length > 0) {
            self.totalFilas = self.items.length;
          }
          self.$loader.close();
        })
        .catch(resp => {
          self.$loader.close();
          if (resp.data && resp.data.mensaje) {
            self.$toast.error(resp.data.mensaje);
          } else {
            self.$toast.error("No se pudo obtener los items");
          }
        });
    },
    seleccionarMesa: function(mesa) {
      var datosRouter = {
        name: 'Detalle Pedido',
        params: {
          mesa: mesa,
          idPedido: mesa.usada
        }
      }
      this.$router.push(datosRouter)
    }
  },
  created: function() {
    this.listarMesas();
  },
  mounted: function() {
    this.$loader.close();
    this.interval = setInterval(() => this.listarMesas(), 30000);
  },
  beforeDestroy: function() {
    clearInterval(this.interval);
  }
};
</script>
