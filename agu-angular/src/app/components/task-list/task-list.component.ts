import { Component } from '@angular/core';
import {TaskService} from "../../services/task.service";
import {NgForOf} from "@angular/common";

@Component({
  selector: 'app-task-list',
  standalone: true,
  imports: [
    NgForOf
  ],
  templateUrl: './task-list.component.html',
  styleUrl: './task-list.component.scss'
})
export class TaskListComponent {
  tasks: any[] = [];

  constructor(private taskService: TaskService) {}

  ngOnInit() {
    this.loadTasks();
  }

  loadTasks() {
    this.taskService.getTasks().subscribe(tasks => {
      this.tasks = tasks;
    });
  }
}
