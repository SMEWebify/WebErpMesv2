<template>
  <div class="row">
    <!-- Columns (Statuses) -->
    <div v-for="status in statuses" :key="status.title" class="col-12 col-lg-6 col-xl-3">
      <div class="card">
        <div class="card-header bg-blue">
          <h5>{{ status.title }}</h5>
        </div>
        <div class="card-body p-3">
          <!-- Tasks -->
          <draggable class="flex-1 overflow-hidden" v-model="status.tasks" v-bind="taskDragOptions" @end="handleTaskMoved">
            <transition-group class="flex-1 flex flex-col h-full overflow-x-hidden overflow-y-auto rounded shadow-xs" tag="div">
              <div v-for="task in status.tasks" :key="task.id" class="card mb-5 bg-light">
                <div class="card-body p-3">
                  <a :href="'/production/Task/Statu/Id/' + task.id">#{{ task.id }}</a> -
                  <span class="font-weight-bold">
                    <a :href="'/orders/' + task.order_lines.orders_id">{{ task.order_lines.order.code }}</a>
                  </span> - {{ task.label }} - {{ task.order_lines.delivery_date }}<br/>
                  <span class="font-weight-bold">{{ task.order_lines.label }} || qty {{ task.order_lines.qty }}</span><br/>
                  <div class="float-right">
                    <div class="row">
                      <div class="col-4">
                        <img v-if="task.service.picture" :src="'/images/methods/'+task.service.picture" class="profile-user-img img-fluid img-circle">
                      </div>
                      <div class="col-8">
                        <span class="font-weight-bold">Setting Time :</span> {{ task.seting_time }}<br/>
                        <span class="font-weight-bold">Unit Time :</span> {{ task.unit_time }}
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </transition-group>
          </draggable>
          <!-- No Tasks -->
          <div v-show="!status.tasks.length && newTaskForStatus !== status.id" class="flex-1 p-4 flex flex-col items-center justify-center">
            <span class="text-gray-600">No tasks yet</span>
          </div>
          <!-- ./No Tasks -->
        </div>
      </div>
    </div>
    <!-- ./Columns -->
  </div>
</template>

<script>
import draggable from "vuedraggable";
export default {
  components: { draggable },
  props: {
    initialData: {
      type: Array,
      required: true
    }
  },
  data() {
    return {
      statuses: this.initialData || [],
      newTaskForStatus: 0
    };
  },
  computed: {
    taskDragOptions() {
      return {
        animation: 200,
        group: "task-list",
        dragClass: "status-drag"
      };
    }
  },
  methods: {
    handleTaskMoved(evt) {
      axios.put("/task/sync", { columns: this.statuses }).catch(err => {
        console.log(err.response);
      });
    }
  }
};
</script>

<style scoped>
.status-drag {
  transition: transform 0.5s;
  transition-property: all;
}
</style>
