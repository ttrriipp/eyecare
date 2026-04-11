package com.example.opticalsystem.ui.bills

import android.view.LayoutInflater
import android.view.ViewGroup
import androidx.core.view.isVisible
import androidx.recyclerview.widget.DiffUtil
import androidx.recyclerview.widget.ListAdapter
import androidx.recyclerview.widget.RecyclerView
import com.example.opticalsystem.data.model.Bill
import com.example.opticalsystem.databinding.ItemBillBinding
import com.example.opticalsystem.util.StatusHelper

class BillAdapter(
    private val onClick: (Bill) -> Unit,
) : ListAdapter<Bill, BillAdapter.BillViewHolder>(BillDiffCallback()) {

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): BillViewHolder {
        val binding = ItemBillBinding.inflate(LayoutInflater.from(parent.context), parent, false)
        return BillViewHolder(binding)
    }

    override fun onBindViewHolder(holder: BillViewHolder, position: Int) {
        holder.bind(getItem(position), onClick)
    }

    class BillViewHolder(
        private val binding: ItemBillBinding,
    ) : RecyclerView.ViewHolder(binding.root) {

        fun bind(bill: Bill, onClick: (Bill) -> Unit) {
            binding.tvInvoiceNumber.text = bill.invoiceNumber
            binding.tvBillDate.text = StatusHelper.formatDateShort(bill.createdAt)

            StatusHelper.applyPaymentStatusBadge(binding.tvPaymentStatus, bill.paymentStatus, bill.paymentStatusLabel)

            val paid = StatusHelper.formatPrice(bill.amountPaid ?: "0")
            val total = StatusHelper.formatPrice(bill.amount)
            binding.tvBillAmount.text = "$paid / $total"

            val balanceVal = bill.balanceDue?.toDoubleOrNull() ?: 0.0
            if (balanceVal > 0 && bill.paymentStatus != "unpaid") {
                binding.tvBalanceDue.isVisible = true
                binding.tvBalanceDue.text = "Balance: ${StatusHelper.formatPrice(bill.balanceDue!!)}"
            } else {
                binding.tvBalanceDue.isVisible = false
            }

            if (bill.orderId != null) {
                binding.tvLinkedOrder.isVisible = true
                binding.tvLinkedOrder.text = "Order #${bill.order?.orderNumber ?: bill.orderId}"
            } else {
                binding.tvLinkedOrder.isVisible = false
            }

            binding.root.setOnClickListener { onClick(bill) }
        }
    }

    class BillDiffCallback : DiffUtil.ItemCallback<Bill>() {
        override fun areItemsTheSame(oldItem: Bill, newItem: Bill): Boolean =
            oldItem.id == newItem.id

        override fun areContentsTheSame(oldItem: Bill, newItem: Bill): Boolean =
            oldItem == newItem
    }
}
