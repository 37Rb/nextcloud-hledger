<template>
	<v-card class="my-2 py-1 pl-1 pr-n2">
		<v-row no-gutters>
			<v-col>
				<v-container :class="$vuetify.breakpoint.name === 'xs' ? 'pa-1' : 'pa-2'">
					<v-row no-gutters>
						<v-col v-if="lblockobj.type === 'transaction'"
							cols="12"
							:sm="(alwaysshowcomments && open) ? 12 : 6"
							lg="3"
							xl="2">
							<v-row no-gutters>
								<v-col cols="7">
									<v-menu
										v-model="datemenu"
										:close-on-content-click="false"
										transition="scale-transition"
										offset-y
										min-width="auto">
										<template v-slot:activator="{ on, attrs }">
											<v-text-field
												v-model="lblockobj.date"
												label="Transaction Date"
												v-bind="attrs"
												v-on="on"
												filled
												hide-details="auto"
												:disabled="onlyenable.length > 0 && !onlyenable.includes('Date')"
												@change="flushTransactionData()"></v-text-field>
										</template>
										<v-date-picker
											v-model="lblockobj.date"
											@input="datemenu = false;flushTransactionData()"></v-date-picker>
									</v-menu>
								</v-col>
								<v-col cols="5">
									<v-select v-if="!showcodeinsteadofstatus"
										hide-details="auto"
										filled
										v-model="lblockobj.status"
										:items="['', '!', '*', '! *']"
										label="Status"
										@change="flushTransactionData"
										:disabled="onlyenable.length > 0 && !onlyenable.includes('Status')"></v-select>
									<v-text-field v-else
										hide-details="auto"
										filled
										label="Code"
										v-model="lblockobj.code"
										@change="flushTransactionData"
										:disabled="onlyenable.length > 0 && !onlyenable.includes('Code')"></v-text-field>
								</v-col>
							</v-row>
						</v-col>
						<v-col
							:cols="open ? 12 : 12"
							:sm="open ? (alwaysshowcomments ? 12 : 6) : 6"
							:lg="open ? (alwaysshowcomments ? 9 : 4) : 2"
							:xl="open ? (alwaysshowcomments ? 10: 5) : 2"
							v-if="lblockobj.type === 'transaction'">
							<v-text-field hide-details="auto"
								filled
								label="Description"
								v-model="lblockobj.description"
								@change="flushTransactionData"
								:disabled="onlyenable.length > 0 && !onlyenable.includes('Description')"></v-text-field>
						</v-col>
						<v-col v-if="(lblockobj.type === 'transaction' && open && alwaysshowcomments)"
							cols="0"
							lg="5"
							xl="4">&nbsp;</v-col>
						<v-col cols="12"
							:sm="alwaysshowcomments ? 12 : 12"
							:lg="alwaysshowcomments ? 7  : 5"
							:xl="alwaysshowcomments ? 8  : 5"
							v-if="(open || alwaysshowcomments) && lblockobj.type === 'transaction'">
							<v-text-field hide-details="auto"
								:filled="open && alwaysshowcomments ? false : true"
								label="Comment"
								v-model="lblockobj.comment"
								@change="flushTransactionData"
								:disabled="onlyenable.length > 0 && !onlyenable.includes('Comment')"></v-text-field>
						</v-col>
						<v-col
							:cols="12"
							:lg="open || (lblockobj.type === 'other' && lblockobj.postingIndexes.length <=0) ? 12 : 5"
							:xl="open || (lblockobj.type === 'other' && lblockobj.postingIndexes.length <=0) ? 12 : 4"
							v-if="lblockobj.type === 'other'">
							<v-textarea hide-details="auto"
								filled
								auto-grow
								label="Generic Ledger Block"
								rows="1"
								@change="flushTransactionData"
								v-model="lblockobj.text"></v-textarea>
						</v-col>
						<v-col cols="12" v-if="lblockobj.type === 'comment'">
							<v-textarea hide-details="auto"
								filled
								auto-grow
								label="Comment Block"
								rows="3"
								@change="flushTransactionData"
								:disabled="onlyenable.length > 0 && !onlyenable.includes('Comment')"
								v-model="lblockobj.text"></v-textarea>
						</v-col>
						<v-col v-if="(lblockobj.type === 'transaction' && !open && alwaysshowcomments)"
							cols="0"
							lg="5"
							xl="4">&nbsp;</v-col>
						<v-col cols="12"
							sm="12"
							lg="7"
							xl="8"
							v-if="!open">
							<v-row no-gutters>
								<v-col cols="12"
									sm="4"
									lg="4"
									xl="5"
									v-if="lblockobj.type !== 'comment' && (lblockobj.postingIndexes.length > 0 || lblockobj.type === 'transaction')">
									<v-combobox hide-details="auto"
										:filled="alwaysshowcomments?false:true"
										v-model="firstAccount"
										:items="accounts"
										label="First Account"
										@change="flushTransactionData"
										:disabled="onlyenable.length > 0 && !onlyenable.includes('Account')"></v-combobox>
								</v-col>
								<v-col cols="12"
									sm="4"
									lg="4"
									xl="2"
									v-if="lblockobj.type !== 'comment' && (lblockobj.postingIndexes.length > 0 || lblockobj.type === 'transaction')">
									<v-text-field hide-details="auto"
										:filled="alwaysshowcomments?false:true"
										label="Amount"
										v-model="firstAmount"
										@change="flushTransactionData"
										:disabled="onlyenable.length > 0 && !onlyenable.includes('Amount')">
										<v-icon slot="prepend" :color="firstAmount.indexOf('-')>=0?'red':'black'">
											{{ firstAmount.indexOf('-') >= 0 ? ($vuetify.breakpoint.name !== 'xs' ? 'mdi-arrow-right-bold' : 'mdi-arrow-down-bold') : ($vuetify.breakpoint.name !== 'xs' ? 'mdi-arrow-left-bold' : 'mdi-arrow-up-bold') }}
										</v-icon>
										<v-icon slot="append-outer" :color="firstAmount.indexOf('-')>=0?'red':'black'">
											{{ firstAmount.indexOf('-') >= 0 ? ($vuetify.breakpoint.name !== 'xs' ? 'mdi-arrow-right-bold' : 'mdi-arrow-down-bold') : ($vuetify.breakpoint.name !== 'xs' ? 'mdi-arrow-left-bold' : 'mdi-arrow-up-bold') }}
										</v-icon>
									</v-text-field>
								</v-col>
								<v-col cols="12"
									sm="4"
									lg="4"
									xl="5"
									v-if="lblockobj.type !== 'comment' && (lblockobj.postingIndexes.length > 0 || lblockobj.type === 'transaction')">
									<v-combobox hide-details="auto"
										:filled="alwaysshowcomments?false:true"
										v-model="secondAccount"
										:items="accounts"
										label="Second Account"
										@change="flushTransactionData"
										:disabled="lblockobj.postingIndexes.length > 2 || (onlyenable.length > 0 && !onlyenable.includes('Second Account'))"></v-combobox>
								</v-col>
							</v-row>
						</v-col>
					</v-row>
					<v-expand-transition v-if="(lblockobj.type === 'other' && lblockobj.text !== '') || lblockobj.type === 'transaction'">
						<div v-show="open">
							<div v-for="(lline,lineIndex) in lblockobj.lines" v-bind:key="lineIndex">
								<v-row v-if="lline.type === 'posting'" no-gutters>
									<v-spacer></v-spacer>
									<v-col cols="7" sm="5" lg="3">
										<v-combobox hide-details="auto"
											v-model="lline.account"
											:items="accounts"
											label="Account"
											@change="flushTransactionData"
											:disabled="onlyenable.length > 0 && !onlyenable.includes('Account')"></v-combobox>
									</v-col>
									<v-col cols="5" sm="3" lg="2">
										<v-text-field hide-details="auto"
											:label="'Amount'+(lline.amount === '' ? ($vuetify.breakpoint.name !== 'xs' ? computedAmountInfo : computedAmountInfo.replace('leftover: ', '')) : '')"
											@change="flushTransactionData"
											v-model="lline.amount"
											:disabled="onlyenable.length > 0 && !onlyenable.includes('Amount')">
											<v-icon v-if="!alwaysshowcomments && $vuetify.breakpoint.name === 'xs'"
												:tabindex="onlyenable.length > 0 ? -1 : undefined"
												@click="deletePosting(lineIndex)"
												slot="append-outer"
												color="red">mdi-delete</v-icon>
										</v-text-field>
									</v-col>
									<v-col cols="12"
										sm="3"
										lg="4"
										v-if="$vuetify.breakpoint.name !== 'xs' || alwaysshowcomments">
										<v-text-field hide-details="auto"
											label="Posting Comment"
											@change="flushTransactionData"
											v-model="lline.comment"
											:disabled="onlyenable.length > 0 && !onlyenable.includes('Comment')">
											<v-icon :tabindex="onlyenable.length > 0 ? -1 : undefined"
												@click="deletePosting(lineIndex)"
												slot="append-outer"
												color="red">mdi-delete</v-icon>
										</v-text-field>
									</v-col>
								</v-row>
							</div>
						</div>
					</v-expand-transition>
				</v-container>
			</v-col>
			<v-col cols="auto">
				<v-container :class="$vuetify.breakpoint.name === 'xs' ? 'px-1' : 'pl-1 pr-2'">
					<v-row justify="center">
						<v-menu offset-y>
							<template v-slot:activator="{ on, attrs }">
								<v-btn icon
									v-bind="attrs"
									v-on="on"
									:tabindex="onlyenable.length > 0 ? -1 : undefined">
									<v-icon>mdi-dots-vertical</v-icon>
								</v-btn>
							</template>
							<v-list>
								<v-list-item @click="deleteTransaction()"><v-list-item-title>Delete</v-list-item-title></v-list-item>
							</v-list>
						</v-menu>
					</v-row>
					<v-row justify="center" v-if="lblockobj.type === 'transaction' || lblockobj.type === 'other'">
						<v-icon icon
							@click="open = !open"
							:tabindex="onlyenable.length > 0 ? -1 : undefined">{{ open ? 'mdi-chevron-up' : 'mdi-chevron-down' }}</v-icon>
					</v-row>
					<v-row justify="center" v-if="lblockobj.type === 'transaction' || lblockobj.type === 'other'">
						<v-icon icon
							class="mt-3"
							v-if="open"
							@click="addPosting()"
							:tabindex="onlyenable.length > 0 ? -1 : undefined"
							color="green">mdi-plus-thick</v-icon>
					</v-row>
				</v-container>
			</v-col>
		</v-row>
	</v-card>
</template>

<script>
export default {
	name: 'LedgerBlock',
	props: ['lblock', 'accounts', 'alwaysshowcomments', 'onlyenable', 'showcodeinsteadofstatus'],
	data() {
		return {
			datemenu: false,
			open: false,
			lblockobj: JSON.parse(JSON.stringify(this.lblock))
		}
	},

	methods: {
		flushTransactionData() {
			this.$emit('change', this.lblockobj)
		},
		addPosting() {
			this.lblockobj.lines.push({ type: 'posting', account: '', amount: '', comment: '', status: '' })
			this.lblockobj.postingIndexes.push(this.lblockobj.lines.length - 1)
			this.flushTransactionData()
		},
		deletePosting(lineIndex) {
			// Remove line
			this.lblockobj.lines.splice(lineIndex, 1)
			// Remove all posting indexes so we can rebuild it
			this.lblockobj.postingIndexes.splice(0, this.lblockobj.postingIndexes.length)
			for (let i = 0; i < this.lblockobj.lines.length; i++) {
				if (this.lblockobj.lines[i].type === 'posting') {
					this.lblockobj.postingIndexes.push(i)
				}
			}
			this.flushTransactionData()
		},
		deleteTransaction() {
			this.$emit('delete-transaction', this)
		}
	},

	watch: {
		lblock: {
			deep: true,
			handler(ob) {
				if (JSON.stringify(this.lblock) !== JSON.stringify(this.lblockobj)) {
					this.lblockobj = JSON.parse(JSON.stringify(this.lblock))
				}
			}
		}
	},

	computed: {
		firstAccount: {
			get() {
				if (this.lblockobj.postingIndexes && this.lblockobj.postingIndexes.length > 0) {
					return this.lblockobj.lines[this.lblockobj.postingIndexes[0]].account
				} else {
					return ''
				}
			},
			set(value) {
				if (this.lblockobj.postingIndexes.length <= 0) {
					this.lblockobj.lines.push({ type: 'posting', account: '', amount: '', comment: '', status: '' })
					this.lblockobj.postingIndexes.push(this.lblockobj.lines.length - 1)
				}
				this.lblockobj.lines[this.lblockobj.postingIndexes[0]].account = value
			}
		},
		firstAmount: {
			get() {
				if (this.lblockobj.postingIndexes && this.lblockobj.postingIndexes.length > 0) {
					return this.lblockobj.lines[this.lblockobj.postingIndexes[0]].amount
				} else {
					return ''
				}
			},
			set(value) {
				if (this.lblockobj.postingIndexes.length <= 0) {
					this.lblockobj.lines.push({ type: 'posting', account: '', amount: '', comment: '', status: '' })
					this.lblockobj.postingIndexes.push(this.lblockobj.lines.length - 1)
				}
				this.lblockobj.lines[this.lblockobj.postingIndexes[0]].amount = value
			}
		},
		secondAccount: {
			get() {
				if (this.lblockobj.postingIndexes && this.lblockobj.postingIndexes.length > 2) {
					return '--- Split ---'
				} else if (this.lblockobj.postingIndexes && this.lblockobj.postingIndexes.length > 1) {
					return this.lblockobj.lines[this.lblockobj.postingIndexes[1]].account
				} else {
					return ''
				}
			},
			set(value) {
				if (this.lblockobj.postingIndexes.length <= 0) {
					this.lblockobj.lines.push({ type: 'posting', account: '', amount: '', comment: '', status: '' })
					this.lblockobj.postingIndexes.push(this.lblockobj.lines.length - 1)
				}
				if (this.lblockobj.postingIndexes.length <= 1) {
					this.lblockobj.lines.push({ type: 'posting', account: '', amount: '', comment: '', status: '' })
					this.lblockobj.postingIndexes.push(this.lblockobj.lines.length - 1)
				}
				if (this.lblockobj.postingIndexes.length === 2) {
					this.lblockobj.lines[this.lblockobj.postingIndexes[1]].account = value
				}
			}
		},
		computedAmountInfo: {
			get() {
				let computeRemaining = true
				let prefixCurrency = null
				let postfixCurrency = null
				let maxDotDecimalPlaces = 0
				let maxCommaDecimalPlaces = 0
				const rawAmounts = []
				for (let i = 0; i < this.lblockobj.postingIndexes.length; i++) {
					const thisAmount = this.lblockobj.lines[this.lblockobj.postingIndexes[i]].amount
					if (thisAmount.length > 0) {
						if (thisAmount.indexOf('=') >= 0) {
							// If there are any assignments or assertions, we cannot compute remaining
							computeRemaining = false
							break
						}
						const thisPrefix = thisAmount.replace('-', '').replace(/[0-9 ,\\.-]*[0-9].*/, '')
						const thisPostfix = thisAmount.replace('-', '').replace(/.*[0-9][0-9 ,\\.-]*/, '')
						const thisRawAmount = thisAmount.replace(/[^0-9,\\.-]+/g, '')
						const thisRawAmountNoDots = thisAmount.replace(/[^0-9,-]+/g, '')
						const thisRawAmountNoCommas = thisAmount.replace(/[^0-9\\.-]+/g, '')
						if (prefixCurrency !== null && prefixCurrency !== thisPrefix) {
							// If there are different currencies, we cannot compute remaining
							computeRemaining = false
							break
						} else {
							prefixCurrency = thisPrefix
						}
						if (postfixCurrency !== null && postfixCurrency !== thisPostfix) {
							// If there are different currencies, we cannot compute remaining
							computeRemaining = false
							break
						} else {
							postfixCurrency = thisPostfix
						}
						if (thisRawAmountNoCommas.indexOf('.') >= 0) {
							const thisDotDecimalPlaces = thisRawAmountNoCommas.length - thisRawAmountNoCommas.indexOf('.') - 1
							if (thisDotDecimalPlaces > maxDotDecimalPlaces) {
								maxDotDecimalPlaces = thisDotDecimalPlaces
							}
						}
						if (thisRawAmountNoDots.indexOf(',') >= 0) {
							const thisCommaDecimalPlaces = thisRawAmountNoDots.length - thisRawAmountNoDots.indexOf(',') - 1
							if (thisCommaDecimalPlaces > maxCommaDecimalPlaces) {
								maxCommaDecimalPlaces = thisCommaDecimalPlaces
							}
						}
						rawAmounts.push(thisRawAmount)
					}
				}
				if (computeRemaining && rawAmounts.length > 0) {
					let decimalPoint = '.'
					let maxDecimalPlaces = maxDotDecimalPlaces
					// Figure out what type of decimal point to use
					if (maxDotDecimalPlaces !== 0 && maxCommaDecimalPlaces !== 0) {
						decimalPoint = (maxDotDecimalPlaces < maxCommaDecimalPlaces) ? '.' : ','
						maxDecimalPlaces = (maxDotDecimalPlaces < maxCommaDecimalPlaces) ? maxDotDecimalPlaces : maxCommaDecimalPlaces
					} else if (maxCommaDecimalPlaces !== 0) {
						decimalPoint = ','
						maxDecimalPlaces = maxCommaDecimalPlaces
					}
					let totalAmount = 0
					// Add up total amount leftover with all transactions
					for (let i = 0; i < rawAmounts.length; i++) {
						let thisDecimalPlaces = 0
						if (rawAmounts[i].indexOf(decimalPoint) >= 0) {
							thisDecimalPlaces = rawAmounts[i].length - rawAmounts[i].indexOf(decimalPoint) - 1
						}
						const thisNumericAmount = parseInt(rawAmounts[i].replace(/[,\\.]/g, ''))
						totalAmount += thisNumericAmount * Math.pow(10, maxDecimalPlaces - thisDecimalPlaces)
					}
					let stringAmount = Math.abs(totalAmount).toString()
					// Pad zeros if necessary
					if (stringAmount.length < maxDecimalPlaces + 1) {
						stringAmount = '0'.repeat((maxDecimalPlaces + 1) - stringAmount.length) + stringAmount
					}
					// Add in the decimal marker, if necessary
					if (maxDecimalPlaces > 0) {
						stringAmount = stringAmount.slice(0, stringAmount.length - maxDecimalPlaces) + decimalPoint + stringAmount.slice(-maxDecimalPlaces)
					}
					if (totalAmount > 0) {
						// Add in minus sign, if necessary
						stringAmount = '-' + stringAmount
					}
					stringAmount = prefixCurrency + stringAmount + postfixCurrency
					return ' (leftover: ' + stringAmount + ')'
				} else {
					return ''
				}
			}
		}
	}
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
.arrow-right-bold {
	color: red;
}
</style>
