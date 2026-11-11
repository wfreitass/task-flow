import { Component } from '@angular/core';
import {TaskService} from "../../services/task.service";
import {FormsModule} from "@angular/forms";

@Component({
  selector: 'app-task',
  standalone: true,
  imports: [
    FormsModule
  ],
  templateUrl: './task.component.html',
  styleUrl: './task.component.scss'
})
export class TaskComponent {
  title: string = '';
  description: string = '';

  constructor(private taskService: TaskService) {}

  addTask() {
    this.taskService.addTask(this.title, this.description).subscribe(() => {
      this.title = '';
      this.description = '';
    });
  }
}
