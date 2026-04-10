package com.example.opticalsystem.ui.checkout

import android.view.LayoutInflater
import android.view.ViewGroup
import androidx.recyclerview.widget.DiffUtil
import androidx.recyclerview.widget.ListAdapter
import androidx.recyclerview.widget.RecyclerView
import com.example.opticalsystem.data.model.Appointment
import com.example.opticalsystem.databinding.ItemAppointmentOptionBinding

class AppointmentOptionAdapter(
    private val onSelect: (Appointment) -> Unit,
) : ListAdapter<Appointment, AppointmentOptionAdapter.AppointmentViewHolder>(DiffCallback()) {

    var selectedId: Int? = null
        set(value) {
            field = value
            notifyDataSetChanged()
        }

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): AppointmentViewHolder {
        val binding = ItemAppointmentOptionBinding.inflate(LayoutInflater.from(parent.context), parent, false)
        return AppointmentViewHolder(binding)
    }

    override fun onBindViewHolder(holder: AppointmentViewHolder, position: Int) {
        holder.bind(getItem(position), selectedId, onSelect)
    }

    class AppointmentViewHolder(
        private val binding: ItemAppointmentOptionBinding,
    ) : RecyclerView.ViewHolder(binding.root) {
        fun bind(item: Appointment, selectedId: Int?, onSelect: (Appointment) -> Unit) {
            binding.root.isChecked = item.id == selectedId
            binding.tvAppointmentType.text = item.appointmentType ?: "Appointment"
            binding.tvDoctor.text = item.doctorName ?: "Doctor to be assigned"
            binding.tvSchedule.text = item.scheduledAt ?: "Schedule to be confirmed"
            binding.root.setOnClickListener { onSelect(item) }
        }
    }

    class DiffCallback : DiffUtil.ItemCallback<Appointment>() {
        override fun areItemsTheSame(oldItem: Appointment, newItem: Appointment): Boolean = oldItem.id == newItem.id
        override fun areContentsTheSame(oldItem: Appointment, newItem: Appointment): Boolean = oldItem == newItem
    }
}
